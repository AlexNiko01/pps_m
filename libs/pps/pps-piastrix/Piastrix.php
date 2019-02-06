<?php

namespace pps\piastrix;

use api\classes\ApiError;
use Yii;
use pps\payment\Payment;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class Piastrix
 * @package pps\piastrix
 */
class Piastrix extends Payment
{
    const API_URL = 'https://core.piastrix.com';

    // The payment was created, waiting for processing.
    const PX_STATUS_CREATED = 1;
    // Waiting for manual confirmation of the operator.
    const PX_STATUS_WAITING_MANUAL_CONFIRMATION = 2;
    // Sent to billing system.
    const PX_STATUS_PS_PROCESSING = 3;
    // Payment error on the payment system side.
    const PX_STATUS_PS_PROCESSING_ERROR = 4;
    // Successfully completed. (Final)
    const PX_STATUS_SUCCESS = 5;
    // Declined on the side of the payment system. (final)
    const PX_STATUS_REJECTED = 6;
    // Confirmed by operator and waiting for posting.
    const PX_STATUS_MANUAL_CONFIRMED = 7;
    // Network error on the payment system side.
    const PX_STATUS_PS_NETWORK_ERROR = 9;

    const RECEIVE_SUCCESS = 'success';
    const RECEIVE_CANCEL = 'cancel';

    /** @var string */
    private $_shop_id;
    /** @var string */
    private $_secret_key;
    /** @var int */
    private $_curl_errno;


    /**
     * Fill the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['secret_key'])) {
            throw new InvalidParamException('secret_key is empty');
        }
        if (empty($data['shop_id'])) {
            throw new InvalidParamException('shop_id is empty');
        }

        $this->_secret_key = $data['secret_key'];
        $this->_shop_id = $data['shop_id'];
    }

    /**
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $validate = static::validateTransaction(
            $params['currency'], $params['payment_method'], $params['amount'], self::WAY_DEPOSIT
        );

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $returns = [
            'fields' => static::getFields($params['currency'], $params['payment_method'], self::WAY_DEPOSIT),
            'currency' => $params['currency'],
            'amount' => $params['amount'],
            'buyer_write_off' => $params['write_off'] ?? null,
            'merchant_refund' => null,
        ];

        return [
            'data' => $returns
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $requests['m_out']->transaction_id = $transaction->id;
        $requests['m_out']->data = $requests['merchant'] ?? [];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        $validate = static::validateTransaction(
            $transaction->currency, $transaction->payment_method, $transaction->amount, self::WAY_DEPOSIT
        );

        if ($validate !== true) {

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $return = [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($return);
            $requests['error']->type = 502;
            $requests['error']->save();

            return $return;
        }

        if (!empty($params['commission_payer']) || !empty($transaction->commission_payer)) {
            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode(
                [
                    'status' => 'error',
                    'message' => self::ERROR_COMMISSION_PAYER
                ]
            );
            $requests['error']->type = 502;
            $requests['error']->save(false);

            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $requisitesOrigin = json_decode($transaction->requisites, true);

        $message = static::_checkRequisites(
            $requisitesOrigin, $transaction->currency, $transaction->payment_method, self::WAY_DEPOSIT
        );

        if ($message !== true) {
            $return = [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($return);
            $requests['error']->type = 502;
            $requests['error']->save(false);

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return $return;
        }

        if ($transaction->payment_method == 'piastrix') {
            return $this->_processPiastrixInvoice($params);
        }

        $params['payway'] = static::_transformPaymentMethod($transaction->payment_method, $transaction->currency);

        $query = [
            'amount' => strval($transaction->amount),
            'currency' => strval($params['currency_iso']),
            'payway' => $params['payway'],
            'shop_id' => $this->_shop_id,
            'shop_order_id' => $transaction->id,
        ];

        $query['sign'] = $this->_getSign($query);
        $query['failed_url'] = $params['fail_url'] .
            (strpos($params['fail_url'], '?') ? '&' : '?') . "txid={$transaction->id}";
        $query['success_url'] = $params['success_url'] .
            (strpos($params['success_url'], '?') ? '&' : '?') . "txid={$transaction->id}";
        $query['description'] = $transaction->comment;

        $requisites = static::_convertFields($requisitesOrigin, $transaction->payment_method, self::WAY_DEPOSIT);

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites);
        }

        $transaction->query_data = json_encode($query);
        $transaction->save(false);

        $requests['out']->transaction_id = $transaction->id;
        $requests['out']->data = $transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $result = $this->_query(static::API_URL . '/invoice/create', $query);

        $transaction->result_data = json_encode($result);
        $transaction->save(false);

        $requests['pa_in']->transaction_id = $transaction->id;
        $requests['pa_in']->data = $transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-piastrix-invoice');

        if (isset($result['result']) && $result['result']) {
            $transaction->external_id = $result['data']['id'];
            $transaction->status = self::STATUS_CREATED;
            $transaction->refund = $transaction->amount;
            $transaction->save(false);

            if (isset($result['data']['data']['PAYMENT_AMOUNT'])) {
                $amount = $result['data']['data']['PAYMENT_AMOUNT'];
            } elseif (isset($result['data']['data']['sum'])) {
                $amount = floatval($result['data']['data']['sum']);
            } elseif (isset($result['data']['data']['amount'])) {
                $amount = floatval($result['data']['data']['amount'] / 100);
            } else {
                //$amount = $transaction->amount;
                $amount = null;
            }

            $transaction->write_off = $amount;
            $transaction->save(false);

            $data_params = '';

            $url = null;

            if ($result['data']['method'] == 'OFFLINE') {
                if (!empty($result['data']['data']['ru'])) {
                    $data_params = $result['data']['data']['ru'];
                }
            } else {
                $data_params = $result['data']['data'];
                $url = $result['data']['url'];
            }

            $answer = [
                'redirect' => [
                    'method' => $result['data']['method'],
                    'url' => $url,
                    'params' => $data_params,
                ],
                'data' => $transaction::getDepositAnswer($transaction)
            ];

        } elseif ($this->_curl_errno != CURLE_OK) {
            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->save(false);

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($result);
            $requests['error']->type = 502;
            $requests['error']->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;
        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer = [
                'status' => 'error',
                'message' => self::ERROR_OCCURRED,
            ];

            if (isset($result['message'])) {
                $answer['code'] = self::_getErrorCodeFromErrorMessage($result['message']);
                $answer['message'] = $result['message'];
            }

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($answer);
            $requests['error']->type = 502;
            $requests['error']->save(false);
        }

        return $answer;
    }

    /**
     * Process piastrix method for invoice
     * @param $params
     * @return array
     */
    public function _processPiastrixInvoice($params)
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        $requisitesOrigin = json_decode($transaction->requisites, true);

        $requisites = static::_convertFields($requisitesOrigin, $transaction->payment_method, self::WAY_DEPOSIT);

        $query = [
            'shop_amount' => strval($transaction->amount),
            'shop_currency' => strval($params['currency_iso']),
            'shop_id' => $this->_shop_id,
            'shop_order_id' => $transaction->id,
            'payer_currency' => strval($params['currency_iso']),
        ];

        $query['sign'] = $this->_getSign($query);
        $query['payer_account'] = $requisites['account'];

        $query['failed_url'] = $params['fail_url'];
        $query['success_url'] = $params['success_url'];

        $transaction->query_data = json_encode($query);
        $transaction->save(false);

        $requests['out']->transaction_id = $transaction->id;
        $requests['out']->data = $transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $result = $this->_query(static::API_URL . '/bill/create', $query);

        $transaction->result_data = json_encode($result);
        $transaction->save(false);

        $requests['pa_in']->transaction_id = $transaction->id;
        $requests['pa_in']->data = $transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-piastrix-invoice-bill');

        if (isset($result['result']) && $result['result']) {
            $transaction->external_id = $result['data']['id'];
            $transaction->write_off = $result['data']['payer_price'];
            $transaction->refund = $result['data']['shop_refund'];
            $transaction->save(false);

            $answer = [
                'redirect' => [
                    'method' => 'GET',
                    'url' => $result['data']['url'],
                    'params' => [],
                ],
                'data' => $transaction::getDepositAnswer($transaction)
            ];

        } elseif ($this->_curl_errno != CURLE_OK) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($result);
            $requests['error']->type = 502;
            $requests['error']->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;
        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer = [
                'status' => 'error',
                'message' => self::ERROR_OCCURRED,
            ];

            if (isset($result['message'])) {
                $answer['code'] = self::_getErrorCodeFromErrorMessage($result['message']);
                $answer['message'] = $result['message'];
            }

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($answer);
            $requests['error']->type = 502;
            $requests['error']->save(false);
        }

        return $answer;
    }

    /**
     * Method for receiving and updating statuses
     * @param array $data
     * @return bool
     */
    public function receive(array $data): bool
    {
        $transaction = $data['transaction'];
        $receiveData = $data['receive_data'];
        $paymentMethod = static::_getPayway($transaction->currency, $transaction->payment_method);

        if (in_array($transaction->status, self::getFinalStatuses())) {
            return true;
        }

        $need = ['sign', 'shop_order_id', 'shop_amount', 'shop_currency', 'status', 'payway'];

        if (!self::checkRequiredParams($need, $receiveData)) {
            $this->logAndDie(
                'Wrong params', [
                'receive_data' => $receiveData,
                'need' => $need,
            ], 'Wrong params', 'piastrix'
            );
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            $this->logAndDie(
                'Wrong sign!',
                'Piastrix receive(). Wrong sign!',
                'Wrong sign!',
                'piastrix-receive'
            );
        }

        if ($receiveData['shop_order_id'] != $transaction->id) {
            $this->logAndDie(
                'Wrong transaction id!',
                'Piastrix receive() transaction id is not set!',
                'Wrong transaction id!',
                'piastrix-receive'
            );
        }

        if (floatval($transaction->amount) != floatval($receiveData['shop_amount'])) {
            $this->logAndDie(
                'Wrong amount',
                "Piastrix receive() transaction amount not equal received amount: Transaction amount = " . floatval(
                    $transaction->amount
                ) . "\nreceived amount = " . floatval($receiveData['shop_amount']),
                'Wrong amount!',
                'piastrix-receive'
            );
        }

        if ($data['currency_iso'] != $receiveData['shop_currency']) {
            $this->logAndDie(
                'Wrong currency!',
                "Piastrix receive() different currency: Merchant currency = {$data['currency_iso']}\nreceived currency = {$receiveData['shop_currency']}",
                'Wrong currency',
                'piastrix-receive'
            );
        }

        if ($receiveData['payway'] != $paymentMethod) {
            $this->logAndDie(
                'Wrong payway!',
                "Piastrix receive() different payment method: Merchant payment method = {$receiveData['payway']}\nreceived payment method = {$paymentMethod}",
                'Wrong payway',
                'piastrix-receive'
            );
        }

        self::_setStatus($transaction, $receiveData['status']);

        if (isset($receiveData['client_price']) && floatval($receiveData['client_price']) > 0) {
            $transaction->write_off = floatval($receiveData['client_price']);
        }

        if (isset($receiveData['shop_refund']) && floatval($receiveData['shop_refund']) > 0) {
            $transaction->refund = floatval($receiveData['shop_refund']);
        }

        if (isset($receiveData['ps_data'])) {
            $ps_data = json_decode($receiveData['ps_data'], true);
            $ps_fields = static::getCallbackFields($transaction->payment_method);
            $callback_data = [];
            $count = 0;

            foreach ($ps_data as $field => $value) {
                $ps_field = 'undefined_' . ++$count;

                if (isset($ps_fields[$field])) {
                    $ps_field = $ps_fields[$field];
                }

                $callback_data[$transaction->payment_method][$ps_field] = $value;
            }

            $transaction->callback_data = json_encode($callback_data);
        }

        $transaction->save(false);

        return true;
    }

    /**
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        $validate = static::validateTransaction(
            $params['currency'], $params['payment_method'], $params['amount'], self::WAY_WITHDRAW
        );

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $answer = [];

        $query = [
            'amount_type' => 'shop_amount',
            'shop_currency' => $params['currency_iso'],
            'amount' => $params['amount'],
            'shop_id' => $this->_shop_id,
        ];

        if ($params['payment_method'] == 'piastrix') {

            $answer['data'] = [
                'fields' => self::getFields($params['currency'], $params['payment_method'], self::WAY_WITHDRAW),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => null,
                'buyer_receive' => null,
            ];

            return $answer;
        }

        $query['payway'] = static::_transformPaymentMethod($params['payment_method'], $params['currency']);

        if ($params['commission_payer'] === self::COMMISSION_BUYER) {
            $query['amount_type'] = 'shop_amount';
        } elseif ($params['commission_payer'] === self::COMMISSION_MERCHANT) {
            $query['amount_type'] = 'ps_amount';
        }

        /*$validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }*/
        $query['sign'] = $this->_getSign($query);

        $preWithdrawResponse = $this->_query(static::API_URL . '/withdraw/try', $query);

        Yii::info(['query' => $query, 'result' => $preWithdrawResponse], 'payment-piastrix-preWithDraw');

        if (isset($preWithdrawResponse['result']) && $preWithdrawResponse['result']) {
            $answer['data'] = [
                'fields' => [],
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => $preWithdrawResponse['data']['shop_write_off'] ?? null,
                'buyer_receive' => $preWithdrawResponse['data']['payee_receive'] ?? null,
            ];

            if (isset($preWithdrawResponse['data']['account_info_config'])) {
                foreach ($preWithdrawResponse['data']['account_info_config'] as $key => $item) {
                    $answer['data']['fields'][$key] = [
                        'regex' => $item['regex'],
                        'label' => $item['title'],
                        'example' => $item['example'] ?? '',
                    ];
                }
            }

            $answer['data']['fields'] = static::_convertFields(
                $answer['data']['fields'], $params['payment_method'], self::WAY_WITHDRAW, true
            );
        } else {
            $answer = [
                'status' => 'error',
                'message' => self::ERROR_OCCURRED,
            ];

            if (isset($preWithdrawResponse['message'])) {
                $answer['message'] = $preWithdrawResponse['message'];
                $answer['code'] = self::_getErrorCodeFromErrorMessage($answer['message']);
            }
        }

        return $answer;
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function withDraw(array $params)
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];
        $answer = [];

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $requests['m_out']->transaction_id = $transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        $validate = $this->validateTransaction(
            $transaction->currency, $transaction->payment_method, $transaction->amount, self::WAY_WITHDRAW
        );

        if ($validate !== true) {

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $return = [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($return);
            $requests['error']->type = 502;
            $requests['error']->save();

            return $return;
        }

        $requisitesOrigin = json_decode($transaction->requisites, true);
        $message = static::_checkRequisites(
            $requisitesOrigin, $transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW
        );

        if ($message !== true) {

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $return = [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($return);
            $requests['error']->type = 502;
            $requests['error']->save(false);

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return $return;
        }

        if ($transaction->payment_method == 'piastrix') {
            return $this->_processPiastrixWithdraw($params);
        }

        $query = [
            'amount' => $transaction->amount,
            'amount_type' => 'shop_amount',
            'shop_currency' => $params['currency_iso'],
            'shop_id' => $this->_shop_id,
            'shop_payment_id' => $transaction->id,
        ];
        $query['payway'] = static::_transformPaymentMethod($transaction->payment_method, $transaction->currency);

        if ($transaction->commission_payer === self::COMMISSION_BUYER) {
            $query['amount_type'] = 'shop_amount';
        } elseif ($transaction->commission_payer === self::COMMISSION_MERCHANT) {
            $query['amount_type'] = 'ps_amount';
        }

        $requisites = static::_convertFields($requisitesOrigin, $transaction->payment_method, self::WAY_WITHDRAW);

        if (!empty($requisites)) {
            if($query['payway'] === 'bank_pln') {
                $requisites['account_details']['holder'] = $requisites['holder'] ?? '';
                ArrayHelper::remove($requisites, 'holder');
            }
            $query = array_merge($query, $requisites);
        }

        $arrayToSign = $this->prepareArrayToSign($query);

        $query['sign'] = $this->_getSign($arrayToSign);

        $query['description'] = $transaction->comment;

        $transaction->query_data = json_encode($query);
        $transaction->save(false);

        $requests['out']->transaction_id = $transaction->id;
        $requests['out']->data = $transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $withdrawResponse = $this->_query(static::API_URL . '/withdraw/create', $query);

        $transaction->result_data = json_encode($withdrawResponse);
        $transaction->save(false);

        $requests['pa_in']->transaction_id = $transaction->id;
        $requests['pa_in']->data = $transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $withdrawResponse], 'payment-piastrix-withdraw');

        if (
            isset($withdrawResponse['result']) &&
            $withdrawResponse['result'] &&
            isset($withdrawResponse['data']) &&
            isset($withdrawResponse['data']['id']) &&
            isset($withdrawResponse['data']['status'])
        ) {
            $transaction->external_id = $withdrawResponse['data']['id'];
            $transaction->receive = floatval($withdrawResponse['data']['payee_receive']);
            $transaction->write_off = floatval($withdrawResponse['data']['shop_write_off']);

            self::_setStatus($transaction, $withdrawResponse['data']['status']);

            $transaction->receive = $withdrawResponse['data']['payee_receive'];
            $transaction->save(false);

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

            if (!in_array($transaction->status, self::getFinalStatuses())) {
                $params['updateStatusJob']->transaction_id = $transaction->id;
            }
        } elseif ($this->_curl_errno == CURLE_OPERATION_TIMEOUTED) {
            if (!in_array($transaction->status, self::getFinalStatuses())) {
                $transaction->status = self::STATUS_TIMEOUT;
                $transaction->save(false);

                $params['updateStatusJob']->transaction_id = $transaction->id;
            }

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } elseif ($this->_curl_errno > CURLE_OK) {

            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->save(false);

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($withdrawResponse);
            $requests['error']->type = 502;
            $requests['error']->save(false);

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } else {

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer = [
                'status' => 'error',
                'message' => self::ERROR_OCCURRED,
            ];

            if (isset($withdrawResponse['message'])) {
                $answer['message'] = $withdrawResponse['message'];
            }

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($answer);
            $requests['error']->type = 502;
            $requests['error']->save(false);
        }

        return $answer;
    }

    /**
     * Process piastrix method for withdraw
     * @param $params
     * @return array
     */
    private function _processPiastrixWithdraw($params)
    {
        /**
         * @var object $transaction
         */
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        $requisitesOrigin = json_decode($transaction->requisites, true);

        $requisites = static::_convertFields($requisitesOrigin, $transaction->payment_method, self::WAY_WITHDRAW);

        $query = [
            'payee_account' => $requisites['account'],
            'amount' => $transaction->amount,
            'payee_currency' => $params['currency_iso'],
            'shop_currency' => $params['currency_iso'],
            'shop_id' => $this->_shop_id,
            'shop_payment_id' => $transaction->id,
            'amount_type' => 'writeoff_amount',
        ];

        if ($transaction->commission_payer === self::COMMISSION_BUYER) {
            $query['amount_type'] = 'writeoff_amount';
        } elseif ($transaction->commission_payer === self::COMMISSION_MERCHANT) {
            $query['amount_type'] = 'receive_amount';
        }

        $query['sign'] = $this->_getSign($query);

        $transaction->query_data = json_encode($query);
        $transaction->save(false);

        $requests['out']->transaction_id = $transaction->id;
        $requests['out']->data = $transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $withdrawResponse = $this->_query(self::API_URL . '/transfer/create', $query);

        $transaction->result_data = json_encode($withdrawResponse);
        $transaction->save(false);

        $requests['pa_in']->transaction_id = $transaction->id;
        $requests['pa_in']->data = $transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $withdrawResponse], 'payment-piastrix-withdraw');

        if (
            isset($withdrawResponse['result']) &&
            $withdrawResponse['result'] &&
            isset($withdrawResponse['data']) &&
            isset($withdrawResponse['data']['id'])
        ) {
            $transaction->external_id = $withdrawResponse['data']['id'];
            $transaction->receive = floatval($withdrawResponse['data']['payee_amount']);
            $transaction->write_off = floatval($withdrawResponse['data']['write_off_amount']);

            $transaction->status = self::STATUS_SUCCESS;
            $transaction->save(false);

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } elseif ($this->_curl_errno == CURLE_OPERATION_TIMEOUTED) {
            $transaction->status = self::STATUS_TIMEOUT;
            $transaction->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } elseif ($this->_curl_errno > CURLE_OK) {

            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->save(false);

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($withdrawResponse);
            $requests['error']->type = 502;
            $requests['error']->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer = [
                'status' => 'error',
                'message' => self::ERROR_OCCURRED,
            ];

            if (isset($withdrawResponse['message'])) {
                $answer['message'] = $withdrawResponse['message'];
            }

            $requests['error']->transaction_id = $transaction->id;
            $requests['error']->data = json_encode($answer);
            $requests['error']->type = 502;
            $requests['error']->save(false);
        }

        return $answer;
    }

    /**
     * Updating not final statuses
     * @param object $transaction
     * @param null|object $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if ($transaction->way === self::WAY_DEPOSIT) {
            return false;
        }

        if ($transaction->payment_method == 'piastrix') {
            $url = static::API_URL . '/transfer/shop_payment_status';
        } else {
            $url = static::API_URL . '/withdraw/shop_payment_status';
        }

        if (!in_array($transaction->status, self::getFinalStatuses())) {
            $query = [
                'shop_payment_id' => $transaction->id,
                'now' => date('Y-m-d H:i:s.s'),
                'shop_id' => $this->_shop_id
            ];

            $query['sign'] = $this->_getSign($query);

            $response = $this->_query($url, $query);

            Yii::info(['query' => $query, 'res' => $response], 'payment-piastrix-status');

            if (isset($model_req)) {
                // Saving the data that came from the PA in the unchanged state
                $model_req->transaction_id = $transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            if (isset($response['result']) && $response['result']) {
                if (empty($transaction->external_id) && isset($response['data']['id'])) {
                    $transaction->external_id = $response['data']['id'];
                }

                if ($transaction->payment_method == 'piastrix') {
                    if (isset($response['data']['id'])) {
                        $transaction->status = self::STATUS_SUCCESS;
                    }
                    if (empty($transaction->receive) && isset($response['data']['payee_amount'])) {
                        $transaction->receive = floatval($response['data']['payee_amount']);
                    }
                    if (empty($transaction->write_off) && isset($response['data']['write_off_amount'])) {
                        $transaction->write_off = floatval($response['data']['write_off_amount']);
                    }
                } else {
                    if (empty($transaction->receive) && isset($response['data']['payee_receive'])) {
                        $transaction->receive = floatval($response['data']['payee_receive']);
                    }
                    if (empty($transaction->write_off) && isset($response['data']['shop_write_off'])) {
                        $transaction->write_off = floatval($response['data']['shop_write_off']);
                    }
                    if (isset($response['data']['status'])) {
                        self::_setStatus($transaction, $response['data']['status']);
                    }
                    if ($transaction->status == self::STATUS_TIMEOUT && isset($response['error_code']) && $response['error_code'] == 7) {
                        $transaction->status = self::STATUS_ERROR;
                    }
                }

                $transaction->save(false);

                return true;
            }
        }

        return false;
    }

    /**
     * @param array $params
     * @return bool
     */
    private function _checkReceivedSign(array $params): bool
    {
        $sign = $params['sign'];
        unset($params['sign']);

        foreach ($params as $key => $value) {
            if (empty($value)) {
                unset($params[$key]);
            }
        }

        $expectedSign = $this->_getSign($params);

        return $expectedSign == $sign;
    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        ksort($dataSet, SORT_STRING);

        $signString = implode(':', $dataSet) . $this->_secret_key;
        //Yii::info($signString, 'withdraw-job');
        return hash('sha256', $signString, false);
    }

    /**
     * Main query method
     * @param string $url
     * @param array $params
     * @return array|false
     */
    private function _query(string $url, array $params)
    {
        $query = json_encode($params);

        $ch = curl_init($url);

        curl_setopt_array(
            $ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
            CURLOPT_TIMEOUT => self::$timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]
        );

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        $this->_curl_errno = curl_errno($ch);

        curl_close($ch);

        Yii::info(
            [
                'curl_info' => [
                    'url' => $info['url'],
                    'http_code' => $info['http_code'],
                    'total_time' => $info['total_time'],
                ],
                'curl_errno' => $this->_curl_errno,
                'curl_error' => $error,
            ], 'payment-piastrix-query-response'
        );

        if ($response !== false) {
            return json_decode($response, true);
        }

        return false;
    }

    /**
     * @param string $currency
     * @param string $payment_method
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $payment_method, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$payment_method]['fields'][$way] ?? [];
    }

    /**
     * Get model for validation incoming data
     * @return \yii\base\Model
     */
    public static function getModel(): \yii\base\Model
    {
        return new Model();
    }

    /**
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        if (isset($data['shop_order_id'])) {
            return ['id' => $data['shop_order_id']];
        } elseif (isset($data['order'])) {
            return ['external_id' => ltrim($data['order'], 'i_')];
        } elseif (isset($data['txid'])) {
            return ['id' => $data['txid']];
        }

        return [];
    }

    /**
     * Get transaction id.
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['shop_order_id'] ?? 0;
    }

    /**
     * Get success answer for stopping send callback data
     * @return string
     */
    public static function getSuccessAnswer()
    {
        return 'OK';
    }

    /**
     * Get response format for success answer
     * @return string
     */
    public static function getResponseFormat()
    {
        return Response::FORMAT_HTML;
    }

    /**
     * Validate transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    private static function validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency][$method])) {
            return "Method '{$method}' does not exist";
        }

        $curr_method = $currencies[$currency][$method];

        if (!isset($curr_method[$way]) || !$curr_method[$way]) {
            return "Payment system does not support '{$way}'";
        }

        if ($way === self::WAY_DEPOSIT) {
            if (isset($curr_method['d_min']) && $curr_method['d_min'] > $amount) {
                self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
                return "Amount should be more than '{$curr_method['d_min']}'";
            } elseif (isset($curr_method['d_max']) && $curr_method['d_max'] <= $amount) {
                self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;
                return "Amount should be less than '{$curr_method['d_max']}'";
            }
        } else {
            if (isset($curr_method['w_min']) && $curr_method['w_min'] > $amount) {
                self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
                return "Amount should be more than '{$curr_method['w_min']}'";
            } elseif (isset($curr_method['w_max']) && $curr_method['w_max'] <= $amount) {
                self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;
                return "Amount should be less than '{$curr_method['w_max']}'";
            }
        }

        if ($amount <= 0) {
            self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            return "Amount should be more than '0'";
        }

        return true;
    }

    /**
     * @param string $paymentMethod
     * @param string $currency
     * @return null|string
     */
    private static function _transformPaymentMethod(string $paymentMethod, string $currency)
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$paymentMethod]['code'] ?? null;
    }

    /**
     * @param string $currency
     * @param string $method
     * @return string
     */
    private static function _getPayway(string $currency, string $method): string
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$method]['code'] ?? '';
    }

    /**
     * @param $message
     * @return int
     */
    private static function _getErrorCodeFromErrorMessage($message): int
    {
        if (preg_match('~ps_amount is too small~', $message)) {
            return ApiError::PAYMENT_MIN_AMOUNT_ERROR;
        }
        if (preg_match('~Payer price amount is too small~', $message)) {
            return ApiError::PAYMENT_MIN_AMOUNT_ERROR;
        }
        if (preg_match('~Insufficient balance~', $message)) {
            return ApiError::LOW_BALANCE;
        }

        return ApiError::PAYMENT_SYSTEM_ERROR;
    }

    /**
     * @param array $inputFields
     * @param string $method
     * @param string $way
     * @param bool $flip
     * @return array
     */
    private static function _convertFields(array $inputFields, string $method, string $way, bool $flip = false): array
    {
        $fields = require(__DIR__ . '/fields.php');
        $outFields = [];
        $replaceFields = [];

        if (isset($fields[$method][$way])) {
            $replaceFields = $flip ? array_flip($fields[$method][$way]) : $fields[$method][$way];
        }

        foreach ($inputFields as $key => $field) {
            $newKey = $replaceFields[$key] ?? false;

            $outFields[$newKey] = $field;
        }

        return $outFields;
    }

    /**
     * @param array $requisites
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return bool|string
     */
    private static function _checkRequisites(array $requisites, string $currency, string $method, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (isset($currencies[$currency][$method]['fields'][$way])) {
            foreach (array_keys($currencies[$currency][$method]['fields'][$way]) as $field) {
                if (!in_array($field, array_keys($requisites))) {
                    return "Required param '{$field}' not found";
                }
            }
        }

        return true;
    }

    /**
     * Checks the connection of payment method
     * @return array
     */
    public static function getListOfPaymentMethod(): array
    {
        return [];
    }

    /**
     * Get currencies list
     * @return array
     */
    public static function getCurrenciesList(): array
    {
        $returns = [];
        $currencies = static::getSupportedCurrencies(false);

        foreach ($currencies as $currency => $arrays) {
            foreach ($arrays AS $key => $value) {
                $returns[$key][] = $currency;
            }

            $returns['dengionline'][] = $currency;
        }

        return $returns;
    }

    /**
     * @param bool $getListCurr
     * @return array
     */
    public static function getApiFields(bool $getListCurr = true): array
    {
        $currencyList = ($getListCurr) ? static::getCurrenciesList() : [];

        return [
            'card' => [
                'RUB' => [
                    self::WAY_DEPOSIT => [],
                    self::WAY_WITHDRAW => [
                        'number' => [
                            'label' => 'Card number',
                            'regex' => '^\\d{16,18}$',
                            'currencies' => ['RUB'],
                        ]
                    ]
                ],
                'USD' => [
                    self::WAY_DEPOSIT => [],
                    self::WAY_WITHDRAW => [
                        'number' => [
                            'label' => 'Card number',
                            'regex' => '^\\d{16,18}$',
                            'currencies' => ['USD'],
                        ]
                    ]
                ],
                'EUR' => [
                    self::WAY_DEPOSIT => [],
                    self::WAY_WITHDRAW => [
                        'number' => [
                            'label' => 'Card number',
                            'regex' => '^\\d{16,18}$',
                            'currencies' => ['EUR'],
                        ]
                    ]
                ],
                'UAH' => [
                    self::WAY_DEPOSIT => [],
                    self::WAY_WITHDRAW => [
                        'number' => [
                            'label' => 'Card number',
                            'regex' => '^(?!404030|410653|413051|414939|414943|414949|414960|414961|414962|414963|417649|423396|424600|424657|432334|432335|432336|432337|432338|432339|432340|432575|434156|440129|440509|440535|440588|458120|458121|458122|462705|462708|473114|473117|473118|473121|476065|476339|513399|516798|516874|516875|516915|516933|516936|517691|521152|521153|521857|530217|532032|532957|535145|536354|544013|545708|545709|552324|557721|558335|558424|670509|676246)[0-9]{16}$',
                            'currencies' => ['UAH'],
                        ]
                    ]
                ],
                'PLN' => [
                    self::WAY_DEPOSIT => [],
                    self::WAY_WITHDRAW => []
                ],
            ],
            'payeer' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'purse' => [
                        'label' => 'Account',
                        'regex' => '^P[0-9]+$',
                        'currencies' => $currencyList['payeer'] ?? [],
                    ]
                ]
            ],
            'nixmoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'account' => [
                        'required' => true,
                        'label' => 'Номер кошелька клиента',
                        'regex' => '^[U|E|B|L|C|F|P|X|D]{1}[0-9]{14}$',
                        'currencies' => $currencyList['nixmoney'] ?? [],
                    ]
                ]
            ],
            'beeline' => [
                self::WAY_DEPOSIT => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789',
                        'currencies' => $currencyList['beeline'] ?? [],
                    ]
                ],
                self::WAY_WITHDRAW => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789',
                        'currencies' => $currencyList['beeline'] ?? [],
                    ]
                ]
            ],
            'mts' => [
                self::WAY_DEPOSIT => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789',
                        'currencies' => $currencyList['mts'] ?? [],
                    ]
                ],
                self::WAY_WITHDRAW => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789',
                        'currencies' => $currencyList['mts'] ?? [],
                    ]
                ]
            ],
            'megafon' => [
                self::WAY_DEPOSIT => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789',
                        'currencies' => $currencyList['megafon'] ?? [],
                    ]
                ],
                self::WAY_WITHDRAW => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789',
                        'currencies' => $currencyList['megafon'] ?? [],
                    ]
                ]
            ],
            'russian-terminals' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'alfaclick' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'card-privat' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    "number" => [
                        "label" => "Номер карты ПриватБанка",
                        "regex" => "^(404030|410653|413051|414939|414943|414949|414960|414961|414962|414963|417649|423396|424600|424657|432334|432335|432336|432337|432338|432339|432340|432575|434156|440129|440509|440535|440588|458120|458121|458122|462705|462708|473114|473117|473118|473121|476065|476339|513399|516798|516874|516875|516915|516933|516936|517691|521152|521153|521857|530217|532032|532957|535145|536354|544013|545708|545709|552324|557721|558335|558424|670509|676246|522119)[0-9]{10}$",
                        'example' => '5218572565325845',
                        'currencies' => $currencyList['card-privat'] ?? [],
                    ],
                ]
            ],
            'privat24' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'promsvyazbank' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'tele2' => [
                self::WAY_DEPOSIT => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(9)\\d{9}$',
                        'example' => '9123456789',
                        'currencies' => $currencyList['tele2'] ?? [],
                    ]
                ],
                self::WAY_WITHDRAW => [
                    'phone' => [
                        'label' => 'Phone number',
                        'regex' => '^(79)[0-9]{9}$',
                        'example' => '79123456789',
                        'currencies' => $currencyList['tele2'] ?? [],
                    ]
                ]
            ],
            'sberonline' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'sofort' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'perfectmoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    "purse" => [
                        "label" => "USD wallet number",
                        "regex" => "^[EU]\\d{6,}$",
                        'example' => 'U12345678',
                        'currencies' => $currencyList['perfectmoney'] ?? [],
                    ]
                ]
            ],
            'qiwi' => [
                self::WAY_DEPOSIT => [
                    'phone' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '^(91|994|82|372|375|374|44|998|972|66|90|81|1|507|7|77|380|371|370|996|9955|992|373|84)[0-9]{6,14}$',
                        'example' => '79123456789',
                        'currencies' => $currencyList['qiwi'] ?? [],
                    ]
                ],
                self::WAY_WITHDRAW => [
                    'phone' => [
                        'label' => 'Qiwi-wallet number',
                        'regex' => '\d{9,15}$',
                        'example' => '79123456789',
                        'currencies' => $currencyList['qiwi'] ?? [],
                    ]
                ]
            ],
            'yamoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'purse' => [
                        'label' => 'Wallet number',
                        'regex' => '^41001\\d*$',
                        'currencies' => $currencyList['yamoney'] ?? [],
                    ]
                ]
            ],
            'webmoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'purse' => [
                        "regex" => "^[ZR]\\d{12}$",
                        "title" => "Номер кошелька",
                        'currencies' => $currencyList['webmoney'] ?? [],
                    ]
                ]
            ],
            'bank' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'account' => [
                        'label' => 'Bank account number',
                        'regex' => '^\d{26}$',
                        'currencies' => $currencyList['bank'] ?? [],
                    ],
                    'holder' => [
                        'label' => 'Account holder name',
                        'regex' => '^[a-zA-Z.-]+ [a-zA-Z.-]+$',
                    ]
                ]
            ],
            'e-payments' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'adv-cash' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'email' => [
                        'label' => 'Email',
                        'regex' => '^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$',
                        'example' => 'mail@example.com',
                        'currencies' => $currencyList['adv-cash'] ?? [],
                    ]
                ]
            ],
            'wex-code' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => []
            ],
            'piastrix' => [
                self::WAY_DEPOSIT => [
                    'account' => [
                        'label' => 'Account',
                        'regex' => '',
                        'currencies' => $currencyList['piastrix'] ?? [],
                    ]
                ],
                self::WAY_WITHDRAW => [
                    'account' => [
                        'label' => 'Account',
                        'regex' => '',
                        'currencies' => $currencyList['piastrix'] ?? [],
                    ]
                ]
            ]
        ];
    }

    /**
     * Get supported currencies and methods
     * @param bool $getListCurr
     * @return array
     */
    public static function getSupportedCurrencies(bool $getListCurr = true): array
    {
        $fields = static::getApiFields($getListCurr);

        return [
            'RUB' => [
                'card' => [
                    'code' => 'card_rub',
                    'name' => 'Банковские карты',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['card']['RUB']
                ],
                'payeer' => [
                    'code' => 'payeer_rub',
                    'name' => 'Payeer',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['payeer']
                ],
                'beeline' => [
                    'code' => 'beeline_rub',
                    'name' => 'Beeline',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['beeline']
                ],
                'mts' => [
                    'code' => 'mts_rub',
                    'name' => 'MTS',
                    'deposit' => true,
                    'withdraw' => true,
                    'd_min' => 10,
                    'fields' => $fields['mts']
                ],
                'megafon' => [
                    'code' => 'megafon_rub',
                    'name' => 'Megafon',
                    'deposit' => true,
                    'withdraw' => true,
                    'w_min' => 10,
                    'w_max' => 191967,
                    'commis' => '1%',
                    'fields' => $fields['megafon']
                ],
                'russian-terminals' => [
                    'code' => 'terminal_rub',
                    'name' => 'Терминалы России',
                    'deposit' => true,
                    'withdraw' => false,
                    'fields' => $fields['russian-terminals']
                ],
                'alfaclick' => [
                    'code' => 'alfaclick_rub',
                    'name' => 'Интернет-банк "Альфа-Клик"',
                    'deposit' => true,
                    'withdraw' => false,
                    'fields' => $fields['alfaclick']
                ],
                'promsvyazbank' => [
                    'code' => 'psbretail_rub',
                    'name' => 'Интернет-банк "Промсвязьбанк"',
                    'deposit' => true,
                    'withdraw' => false,
                    'fields' => $fields['promsvyazbank']
                ],
                'tele2' => [
                    'code' => 'tele2_rub',
                    'name' => 'Tele2',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['tele2']
                ],
                'sberonline' => [
                    'code' => 'sberonline_rub',
                    'name' => 'Сбербанк Онлайн',
                    'deposit' => true,
                    'withdraw' => false,
                    'fields' => $fields['sberonline']
                ],
                'qiwi' => [
                    'code' => 'qiwi_rub',
                    'name' => 'QIWI',
                    'deposit' => true,
                    'withdraw' => true,
                    'd_min' => 1,
                    'd_max' => 15000,
                    'w_min' => 1,
                    'w_max' => 15000,
                    'fields' => $fields['qiwi']
                ],
                'yamoney' => [
                    'code' => 'yamoney_rub',
                    'name' => 'Yandex.Money',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['yamoney']
                ],
                'webmoney' => [
                    'code' => 'webmoney_rub',
                    'name' => 'Webmoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['webmoney']
                ],
                'bank' => [
                    'code' => 'bank_rub',
                    'name' => 'Bank',
                    'deposit' => false,
                    'withdraw' => true,
                    'fields' => $fields['bank']
                ],
                'adv-cash' => [
                    'code' => 'advcash_rub',
                    'name' => 'AdvCash',
                    'deposit' => false,
                    'withdraw' => true,
                    'w_min' => 10,
                    'w_max' => 191967,
                    'commis' => '1%',
                    'fields' => $fields['adv-cash']
                ],
                'wex-code' => [
                    'code' => 'wex_rub',
                    'name' => 'WEX-Code',
                    'deposit' => false,
                    'withdraw' => true,
                    'fields' => $fields['adv-cash']
                ],
                'piastrix' => [
                    'code' => 'piastrix_rub',
                    'name' => 'Piastrix',
                    'd_min' => 0.02,
                    'd_max' => 100000,
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['piastrix']
                ],
            ],
            'USD' => [
                'card' => [
                    'code' => 'card_usd',
                    'name' => 'Банковские карты',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['card']['USD']
                ],
                'payeer' => [
                    'code' => 'payeer_usd',
                    'name' => 'Payeer',
                    'deposit' => true,
                    'withdraw' => true,
                    'd_min' => 1,
                    'd_max' => 10000,
                    'fields' => $fields['payeer']
                ],
                'nixmoney' => [
                    'code' => 'nixmoney_usd',
                    'name' => 'NixMoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['nixmoney']
                ],
                'perfectmoney' => [
                    'code' => 'perfectmoney_usd',
                    'name' => 'PerfectMoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['perfectmoney']
                ],
                'qiwi' => [
                    'code' => 'qiwi_usd',
                    'name' => 'QIWI',
                    'deposit' => true,
                    'withdraw' => true,
                    'd_min' => 0.5,
                    'd_max' => 4000,
                    'w_min' => 1,
                    'w_max' => 200,
                    'fields' => $fields['qiwi']
                ],
                'webmoney' => [
                    'code' => 'webmoney_usd',
                    'name' => 'Webmoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['webmoney']
                ],
                'e-payments' => [
                    'code' => 'epayments_usd',
                    'name' => 'ePayments',
                    'deposit' => false,
                    'withdraw' => true,
                    'fields' => $fields['e-payments']
                ],
                'adv-cash' => [
                    'code' => 'advcash_usd',
                    'name' => 'AdvCash',
                    'deposit' => false,
                    'withdraw' => true,
                    'w_min' => 0.1,
                    'w_max' => 3000,
                    'commis' => '1%',
                    'fields' => $fields['adv-cash']
                ],
                'wex-code' => [
                    'code' => 'wex_usd',
                    'name' => 'WEX-Code',
                    'deposit' => false,
                    'withdraw' => true,
                    'w_min' => 0.1,
                    'w_max' => 10000,
                    'commis' => '3%',
                    'fields' => $fields['wex-code']
                ],
                'piastrix' => [
                    'code' => 'piastrix_usd',
                    'name' => 'Piastrix',
                    'd_min' => 0.02,
                    'd_max' => 100000,
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['piastrix']
                ],
            ],
            'EUR' => [
                'card' => [
                    'code' => 'card_eur',
                    'name' => 'Банковские карты',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['card']['EUR']
                ],
                'payeer' => [
                    'code' => 'payeer_eur',
                    'name' => 'Payeer',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['payeer']
                ],
                'nixmoney' => [
                    'code' => 'nixmoney_eur',
                    'name' => 'NixMoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['nixmoney']
                ],
                'perfectmoney' => [
                    'code' => 'perfectmoney_eur',
                    'name' => 'PerfectMoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['perfectmoney']
                ],
                'qiwi' => [
                    'code' => 'qiwi_eur',
                    'name' => 'QIWI',
                    'deposit' => true,
                    'withdraw' => true,
                    'd_min' => 0.5,
                    'd_max' => 4000,
                    'w_min' => 1,
                    'w_max' => 200,
                    'fields' => $fields['qiwi']
                ],
                'webmoney' => [
                    'code' => 'webmoney_eur',
                    'name' => 'Webmoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['webmoney']
                ],
                'adv-cash' => [
                    'code' => 'advcash_eur',
                    'name' => 'AdvCash',
                    'deposit' => false,
                    'withdraw' => true,
                    'w_min' => 0,
                    'w_max' => 2700,
                    'commis' => '1%',
                    'fields' => $fields['adv-cash']
                ],
                'piastrix' => [
                    'code' => 'piastrix_eur',
                    'name' => 'Piastrix',
                    'd_min' => 0.02,
                    'd_max' => 100000,
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['piastrix']
                ],
            ],
            'UAH' => [
                'card' => [
                    'code' => 'card_uah',
                    'name' => 'Банковские карты',
                    'deposit' => true,
                    'withdraw' => true,
                    'd_min' => 1,
                    'd_max' => 25000,
                    'w_min' => 1,
                    'w_max' => 14999,
                    'commis' => '1.5% + 10.0',
                    'fields' => $fields['card']['UAH']
                ],
                'card-privat' => [
                    'code' => 'card_privat_uah',
                    'name' => 'Карты ПриватБанка',
                    'deposit' => false,
                    'withdraw' => true,
                    'w_min' => 1,
                    'w_max' => 14999,
                    'commis' => '1.5% + 10.0',
                    'fields' => $fields['card-privat']
                ],
                'privat24' => [
                    'code' => 'privat24_uah',
                    'name' => 'Приват24',
                    'deposit' => true,
                    'withdraw' => false,
                    'd_min' => 1,
                    'd_max' => 30000,
                    'fields' => $fields['privat24']
                ],
                'webmoney' => [
                    'code' => 'webmoney_uah',
                    'name' => 'Webmoney',
                    'deposit' => true,
                    'withdraw' => true,
                    'fields' => $fields['webmoney']
                ],
            ],
            'PLN' => [
                'card' => [
                    'code' => 'card_pln',
                    'name' => 'Банковские карты',
                    'deposit' => true,
                    'withdraw' => false,
                    'fields' => $fields['card']['PLN']
                ],
                'sofort' => [
                    'code' => 'sofort_pln',
                    'name' => 'Sofort',
                    'deposit' => true,
                    'withdraw' => false,
                    'fields' => $fields['sofort']
                ],
                'bank' => [
                    'code' => 'bank_pln',
                    'name' => 'Bank',
                    'deposit' => false,
                    'withdraw' => true,
                    'fields' => $fields['bank']
                ],
            ],
        ];
    }

    /**
     * @param $transaction
     * @param $status
     */
    private static function _setStatus($transaction, $status)
    {
        switch ($status) {
            case self::PX_STATUS_CREATED:
                $transaction->status = self::STATUS_CREATED;
                break;
            case self::PX_STATUS_WAITING_MANUAL_CONFIRMATION:
                $transaction->status = self::STATUS_UNCONFIRMED;
                break;
            case self::PX_STATUS_PS_PROCESSING:
            case self::PX_STATUS_MANUAL_CONFIRMED:
                $transaction->status = self::STATUS_PENDING;
                break;
            case self::PX_STATUS_PS_PROCESSING_ERROR:
                $transaction->status = self::STATUS_PENDING_ERROR;
                break;
            case self::PX_STATUS_SUCCESS:
            case self::RECEIVE_SUCCESS:
                $transaction->status = self::STATUS_SUCCESS;
                break;
            case self::PX_STATUS_REJECTED:
            case self::RECEIVE_CANCEL:
                $transaction->status = self::STATUS_CANCEL;
                break;
            case self::PX_STATUS_PS_NETWORK_ERROR:
                $transaction->status = self::STATUS_NETWORK_ERROR;
                break;
        }
    }

    /**
     * Get callback fields
     * @param string $payment_method
     * @return array
     */
    private static function getCallbackFields(string $payment_method): array
    {
        $data = [
            'card' => [
                'ps_payer_account' => 'number'
            ],
            'qiwi' => [
                "prv_name" => "name",
                "ps_payer_account" => "phone",
                "user" => "comment"
            ],
            'privat24' => [
                'ps_payer_account' => 'number'
            ],
            'yamoney' => [
                'ps_payer_account' => 'purse'
            ],
            'payeer' => [],
            'beeline' => [],
            'mts' => [],
            'megafon' => [],
            'russian-terminals' => [],
            'alfaclick' => [],
            'promsvyazbank' => [],
            'tele2' => [],
            'sberonline' => [],
            'webmoney' => [],
            'bank' => [],
            'adv-cash' => [],
            'wex-code' => [],
            'nixmoney' => [],
            'perfectmoney' => [],
            'e-payments' => [],
            'card-privat' => [],
            'sofort' => [],
            'piastrix' => [
                'ps_payer_account' => 'account'
            ]
        ];

        return $data[$payment_method] ?? [];
    }

    /**
     * @param array $query
     * @return array
     */
    private function prepareArrayToSign(array $query): array
    {
        ArrayHelper::remove($query, 'account_details');
        return $query;
    }
}