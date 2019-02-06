<?php

namespace pps\trio;

use api\classes\ApiError;
use Yii;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * Class Trio
 * @package pps\trio
 */
class Trio extends Payment
{
    const API_URL = 'https://central.pay-trio.com';
    const TIP_URL = 'https://tip.pay-trio.com/en/';

    /**
     * @var object
     */
    private $_transaction;
    /**
     * @var string
     */
    private $_shop_id;
    /**
     * @var string
     */
    private $_secret_key;
    /**
     * @var null|string
     */
    private $_query_error;
    /**
     * @var array
     */
    private $_query_info;
    /**
     * @var int
     */
    private $_query_errno;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['secret_key'])) {
            throw new InvalidParamException('secret_key empty');
        }
        if (empty($data['shop_id'])) {
            throw new InvalidParamException('shop_id empty');
        }

        $this->_secret_key = $data['secret_key'];
        $this->_shop_id = $data['shop_id'];
    }

    /**
     * @param array $data
     * @return array
     */
    public function preInvoice(array $data): array
    {
        $payment_method = self::_transformPaymentMethod($data['payment_method'], $data['currency']);

        $query = [
            'shop_id' => $this->_shop_id,
            'currency' => $data['currency_iso'],
            'amount' => $data['amount'],
            'payway' => $payment_method
        ];

        $query['sign'] = $this->_getSign($query);

        $validate = self::_validateTransaction($data['currency'], $data['payment_method'], $data['amount'], Payment::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        if (!empty($data['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $result = $this->_query('pre_invoice', $query);

        $answer = [];

        Yii::info($result, 'payment-trio');

        if (isset($result['result']) && $result['result'] === 'ok') {

            $answer['data']['fields'] = [];
            $answer['data']['currency'] = $data['currency'];
            $answer['data']['amount'] = $data['amount'];
            $answer['data']['buyer_write_off'] = $result['data']['client_price'];
            $answer['data']['merchant_refund'] = round($result['data']['shop_refund'], 2);

            if (isset($result['data']['add_ons_config'])) {
                if (!isset($data['interface']) || $data['interface'] != true) {
                    foreach ($result['data']['add_ons_config'] as $key => $item) {
                        $answer['data']['fields'][$key] = [
                            'regex' => $item['regex'],
                            'label' => $item['label']['en'],
                            'example' => $item['example'] ?? '',
                        ];
                    }
                }
            }

            $answer['data']['fields'] = self::_convertFields($answer['data']['fields'], $data['payment_method'], Payment::WAY_DEPOSIT, true);

        } else {
            $answer['status'] = 'error';

            if (isset($result['message'])) {
                $answer['message'] = $result['message'];
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
            }
        }

        return $answer;
    }

    /**
     * @param array $data
     * @return array
     */
    public function invoice(array $data): array
    {
        $this->_transaction = $data['transaction'];
        $requests = $data['requests'];

        if (!empty($this->_transaction->commission_payer)) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $payment_method = self::_transformPaymentMethod($this->_transaction->payment_method, $this->_transaction->currency);
        $data['payway'] = $payment_method;

        $validate = self::_validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, Payment::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $requisitesOrigin = json_decode($this->_transaction->requisites, true);

        $message = self::_checkRequisites($requisitesOrigin, $this->_transaction->currency, $this->_transaction->payment_method, Payment::WAY_DEPOSIT);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $answer = [];

        try {
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_TRANSACTION_ID
            ];
        }

        $requests['m_out']->transaction_id = $this->_transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        if (isset($data['interface']) && $data['interface'] == true) {
            return $this->_invoiceInterface($data);
        }

        $query = [
            'shop_id' => $this->_shop_id,
            'shop_invoice_id' => $this->_transaction->id,
            'currency' => $data['currency_iso'],
            'amount' => $this->_transaction->amount,
            'payway' => $payment_method
        ];

        $query['sign'] = $this->_getSign($query);
        $query['description'] = $this->_transaction->comment;

        $query['failed_url'] = $data['fail_url'];
        $query['success_url'] = $data['success_url'];

        $requisites = self::_convertFields($requisitesOrigin, $this->_transaction->payment_method, Payment::WAY_DEPOSIT);

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites);
        }

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $result = $this->_query('invoice', $query);

        $this->_transaction->result_data = json_encode($result);

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-trio-invoice');

        if (isset($result['result']) && $result['result'] === 'ok') {

            $this->_transaction->external_id = $result['data']['invoice_id'];
            $this->_transaction->status = self::STATUS_CREATED;
            $this->_transaction->save(false);

            $answer['redirect'] = [
                'method' => $result['data']['method'],
                'url' => $result['data']['source'],
                'params' => $result['data']['data'],
            ];

            $answer['data'] = $this->_transaction::getDepositAnswer($this->_transaction);

        } else if ($this->_query_errno != CURLE_OK) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;

        } else {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save();

            $answer['status'] = 'error';

            if (isset($result['message'])) {
                $answer['message'] = $result['message'];
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
            }

            $message = "Request url = " . self::API_URL . '/invoice';
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-trio-invoice');
        }

        return $answer;
    }

    /**
     * Use trio interface
     * @param array $data
     * @return mixed
     */
    private function _invoiceInterface(array $data)
    {
        $requests = $data['requests'];

        if (!empty($data['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $query = [
            'amount' => $this->_transaction->amount,
            'currency' => $data['currency_iso'],
            'shop_id' => $this->_shop_id,
            'shop_invoice_id' => $this->_transaction->id
        ];

        $query['sign'] = $this->_getSign($query);

        $query['description'] = $this->_transaction->comment;
        $query['failed_url'] = $data['fail_url'];
        $query['success_url'] = $data['success_url'];
        $query['paymethod_id'] = $this->_getPaymentMethodID($this->_transaction->currency, $this->_transaction->payment_method);

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $this->_transaction->result_data = json_encode([]);

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => []], 'payment-trio-invoice');

        $this->_transaction->external_id = '';
        $this->_transaction->status = self::STATUS_CREATED;
        $this->_transaction->save(false);

        $answer['redirect'] = [
            'method' => 'POST',
            'url' => self::TIP_URL,
            'params' => $query,
        ];

        $answer['data'] = $answer['data'] = $this->_transaction::getDepositAnswer($this->_transaction);

        return $answer;
    }

    /**
     * Method for receiving and updating statuses
     * @param array $data
     * @return bool
     */
    public function receive(array $data): bool
    {
        $this->_transaction = $data['transaction'];
        $receiveData = $data['receive_data'];

        if (in_array($this->_transaction->status, Payment::getFinalStatuses())) {
            return true;
        }

        $need = ['shop_amount', 'shop_currency', 'payway', 'status'];

        if (!self::checkRequiredParams($need, $receiveData)) {
            Yii::error([
                'receive_data' => $receiveData,
                'need' => $need
            ], 'payment-trio-receive');
            return false;
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        $expectedAmount = (float)$this->_transaction->amount;
        $amount = (float)$receiveData['shop_amount'];

        // If amounts not equal
        if ($expectedAmount != $amount) {
            $this->logAndDie('Trio receive() transaction amount not equal received amount',
                "Transaction amount = {$this->_transaction->amount}\nreceived amount = {$receiveData['shop_amount']}",
                "Transaction amount not equal received amount");
        }

        // If different currency
        if ($data['currency_iso'] != $receiveData['shop_currency']) {
            Yii::error([
                'title' => "Trio receive() different currency",
                'transaction_id' => $receiveData['shop_invoice_id'] ?? 'undefined',
                'message' => "Merchant currency = {$data['currency_iso']}\nreceived currency = {$receiveData['shop_currency']}",
            ], 'payment-trio-receive');

            die("Different currency");
        }

        // If different payment method
        $paymentMethod = self::_getPayway($this->_transaction->currency, $this->_transaction->payment_method);
        if ($receiveData['payway'] != $paymentMethod) {
            Yii::error([
                'title' => "Trio receive() different payment method",
                'transaction_id' => $receiveData['shop_invoice_id'] ?? 'undefined',
                'message' => "Merchant payment method = {$receiveData['payway']}\nreceived payment method = {$paymentMethod}",
            ], 'payment-trio-receive');

            die("Different payment method");
        }

        if ($this->_transaction->way === Payment::WAY_DEPOSIT) {

            if (empty($this->_transaction->write_off)) {
                $this->_transaction->write_off = $receiveData['client_price'];
            }

            if (empty($this->_transaction->refund)) {
                $this->_transaction->refund = $receiveData['shop_refund'];
            }

            if (self::_isSuccessDepositStatus($receiveData['status'])) {
                $this->_transaction->status = self::STATUS_SUCCESS;
            } else {
                if ($receiveData['status'] === 4) {
                    $this->_transaction->status = self::STATUS_CANCEL;
                }
            }
        } else {
            $this->_changeWithdrawTransactionStatus($this->_transaction, $receiveData['status']);
        }

        $this->_transaction->save(false);

        return true;

    }

    /**
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        $payment_method = self::_transformPaymentMethod($params['payment_method'], $params['currency']);

        $query = [
            'shop_id' => $this->_shop_id,
            'amount' => $params['amount'],
            'amount_type' => 'shop_amount',
            'purse_currency' => $params['currency_iso'],
            'payway' => $payment_method,
        ];

        if ($params['commission_payer'] === Payment::COMMISSION_BUYER) {
            $query['amount_type'] = 'shop_amount';
        } elseif ($params['commission_payer'] === Payment::COMMISSION_MERCHANT) {
            $query['amount_type'] = 'ps_amount';
        }

        $validate = self::_validateTransaction($params['currency'], $params['payment_method'], $params['amount'], Payment::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $query['sign'] = $this->_getSign($query);

        $answer = [];

        $result = $this->_query('pre_withdraw', $query);

        Yii::info(['query' => $query, 'result' => $result], 'payment-trio');

        if (isset($result['result']) && $result['result'] === 'ok') {

            $answer['data'] = [
                'fields' => [],
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => $result['data']['shop_write_off'],
                'buyer_receive' => $result['data']['payee_receive']
            ];

            if (isset($result['data']['account_info_config'])) {
                foreach ($result['data']['account_info_config'] as $key => $item) {
                    $answer['data']['fields'][$key] = [
                        'regex' => $item['regex'],
                        'label' => $item['title'],
                        'example' => $item['example'] ?? '',
                    ];
                }
            }

            $answer['data']['fields'] = self::_convertFields($answer['data']['fields'], $params['payment_method'], Payment::WAY_WITHDRAW, true);

        } else {

            $answer['status'] = 'error';

            if (isset($result['message'])) {
                $answer['message'] = $result['message'];
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
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
        $this->_transaction = $params['transaction'];
        $requests = $params['requests'];

        $payment_method = self::_transformPaymentMethod($this->_transaction->payment_method, $this->_transaction->currency);

        $validate = self::_validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, Payment::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        try {
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_TRANSACTION_ID
            ];
        }

        $requests['m_out']->transaction_id = $this->_transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        $query = [
            'shop_id' => $this->_shop_id,
            'amount' => $this->_transaction->amount,
            'amount_type' => 'shop_amount',
            'purse_currency' => $params['currency_iso'],
            'payway' => $payment_method,
            'payment_id' => $this->_transaction->id,
        ];

        if ($this->_transaction->commission_payer === Payment::COMMISSION_BUYER) {
            $query['amount_type'] = 'shop_amount';
        } elseif ($this->_transaction->commission_payer === Payment::COMMISSION_MERCHANT) {
            $query['amount_type'] = 'ps_amount';
        }

        $requisitesOrigin = json_decode($this->_transaction->requisites, true);

        $message = self::_checkRequisites($requisitesOrigin, $this->_transaction->currency, $this->_transaction->payment_method, Payment::WAY_DEPOSIT);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $requisites = self::_convertFields($requisitesOrigin, $this->_transaction->payment_method, Payment::WAY_WITHDRAW);

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites);
        }

        $query['sign'] = $this->_getSign($query);

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $withdrawResponse = $this->_query('withdraw', $query);

        $this->_transaction->result_data = json_encode($withdrawResponse);

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $withdrawResponse], 'payment-trio-withdraw');

        $answer = [];

        if (isset($withdrawResponse['result']) && $withdrawResponse['result'] === 'ok' && isset($withdrawResponse['data']) && isset($withdrawResponse['data']['id']) && isset($withdrawResponse['data']['status'])) {

            $this->_transaction->external_id = $withdrawResponse['data']['id'];
            $this->_transaction->receive = round($withdrawResponse['data']['payee_receive'], 2);

            $this->_transaction->write_off = $withdrawResponse['data']['shop_write_off'];

            $this->_changeWithdrawTransactionStatus($this->_transaction, $withdrawResponse['data']['status']);

            $this->_transaction->save(false);

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

            if (!self::_isFinalWithdrawStatus($withdrawResponse['data']['status'])) {
                $params['updateStatusJob']->transaction_id = $this->_transaction->id;
            }

        } else if ($this->_query_errno == CURLE_OPERATION_TIMEOUTED) {
            $this->_transaction->status = self::STATUS_TIMEOUT;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } else if ($this->_query_errno != CURLE_OK) {
            $this->_transaction->status = self::STATUS_NETWORK_ERROR;
            $this->_transaction->result_data = $this->_query_errno;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } else {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save();

            if (isset($withdrawResponse['message'])) {
                $answer['message'] = $withdrawResponse['message'];
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
            }

            $answer['status'] = 'error';

            $message = "Request url = " . self::API_URL . '/withdraw';
            $message .= "\nRequest result = " . print_r($withdrawResponse, true);

            Yii::error($message, 'payment-trio-withdraw');
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
        $this->_transaction = $transaction;

        if ($transaction->way === Payment::WAY_DEPOSIT) {
            return false;
        }

        if (in_array($transaction->status, Payment::getNotFinalStatuses())) {

            if (!empty($this->_transaction->external_id)) {
                $query = [
                    'withdraw_id' => $this->_transaction->external_id,
                    'now' => date('Y-m-d H:i:s.s'),
                    'shop_id' => $this->_shop_id
                ];
                $path = 'withdraw_status';
            } else {
                $query = [
                    'shop_payment_id' => $this->_transaction->id,
                    'now' => date('Y-m-d H:i:s.s'),
                    'shop_id' => $this->_shop_id
                ];
                $path = 'shop_payment_status';
            }

            $query['sign'] = $this->_getSign($query);

            $response = $this->_query($path, $query);

            if (!in_array($this->_query_info['http_code'], [200, 201])) {
                Yii::error(['query' => $query, 'res' => $response, 'http_code' => $this->_query_info['http_code']], 'payment-trio-status');
            } else {
                Yii::info(['query' => $query, 'res' => $response], 'payment-trio-status');
            }

            if (isset($model_req)) {
                // Saving the data that came from the PA in the unchanged state
                $model_req->transaction_id = $this->_transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            if (isset($response['result']) && $response['result'] === 'ok') {
                if (isset($response['data']['status'])) {
                    $this->_changeWithdrawTransactionStatus($this->_transaction, $response['data']['status']);
                    $this->_transaction->save(false);
                }

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

        $signCheckArray = array_filter($params, function ($v) {
            return $v !== null && $v !== '';
        }
        );

        $expectedSign = $this->_getSign($signCheckArray);
        // If sign is wrong
        if ($sign != $expectedSign) {
            Yii::error("Trio receive() wrong sign is received: expectedSign = {$expectedSign}\nSign = {$sign}", 'payment-trio-receive');
        } else {
            return true;
        }

        return false;

    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        ksort($dataSet, SORT_STRING);
        $signString = implode(':', $dataSet);
        $signString .= $this->_secret_key;

        return md5($signString);
    }

    /**
     * Main query method
     * @param string $path
     * @param array $params
     * @return boolean|object
     */
    private function _query(string $path, array $params)
    {
        $url = self::API_URL . '/' . trim($path, '/');
        $query = json_encode($params);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => false,
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
        ]);

        $response = curl_exec($ch);
        $this->_query_info = curl_getinfo($ch);
        $this->_query_error = curl_error($ch);
        $this->_query_errno = curl_errno($ch);

        curl_close($ch);

        if ($response === false) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    /**
     * Getting supported currencies and methods
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return require(__DIR__ . '/currency_lib.php');
    }

    /**
     * Getting model for validation incoming data
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
        if (isset($data['shop_invoice_id'])) {
            return ['id' => $data['shop_invoice_id']];
        } else {
            return isset($data['order']) ? ['external_id' => $data['order']] : [];
        }
    }

    /**
     * Getting transaction id.
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['shop_invoice_id'] ?? 0;
    }

    /**
     * Getting success answer for stopping send callback data
     * @return string
     */
    public static function getSuccessAnswer()
    {
        return 'OK';
    }

    /**
     * Getting response format for success answer
     * @return string
     */
    public static function getResponseFormat()
    {
        return Response::FORMAT_HTML;
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        $currencies = self::getSupportedCurrencies();

        $paymentMethods = [];
        $errors = [];

        foreach ($currencies as $currency => $methods) {
            foreach ($methods as $key => $method) {
                if (!isset($paymentMethods[$key])) {
                    if (isset($method['fields']['deposit']) && $method['fields']['deposit'] !== [false]) {
                        $paymentMethods[$key]['deposit'] = $method['fields']['deposit'];
                        foreach ($paymentMethods[$key]['deposit'] as $name => $paymentMethod) {
                            $paymentMethods[$key]['deposit'][$name]['currencies'] = [$currency];
                            $paymentMethods[$key]['deposit'][$name]['required'] = true;
                        }
                    }

                    if (isset($method['fields']['withdraw']) && $method['fields']['withdraw'] !== [false]) {
                        $paymentMethods[$key]['withdraw'] = $method['fields']['withdraw'];
                        foreach ($paymentMethods[$key]['withdraw'] as $name => $paymentMethod) {
                            $paymentMethods[$key]['withdraw'][$name]['currencies'] = [$currency];
                            $paymentMethods[$key]['withdraw'][$name]['required'] = true;
                        }
                    }
                } else {
                    if (isset($paymentMethods[$key]['deposit'])) {
                        if (isset($method['fields']['deposit']) && $method['fields']['deposit'] !== [false]) {
                            foreach ($method['fields']['deposit'] as $fieldName => $field) {
                                if (!isset($paymentMethods[$key]['deposit'][$fieldName])) {
                                    $paymentMethods[$key]['deposit'][$fieldName] = $field;
                                    foreach ($paymentMethods[$key]['deposit'] as $name => $paymentMethod) {
                                        $paymentMethods[$key]['deposit'][$name]['currencies'] = [$currency];
                                    }
                                } else {
                                    $paymentMethods[$key]['deposit'][$fieldName]['currencies'][] = $currency;
                                    if ($paymentMethods[$key]['deposit'][$fieldName]['regex'] != $field['regex']) {
                                        $errors[] = "Please fix this regex: $currency => $key => deposit => $fieldName";
                                    }
                                }
                            }
                        }

                    } else {
                        if (isset($method['fields']['deposit']) && $method['fields']['deposit'] !== [false]) {
                            $paymentMethods[$key]['deposit'] = $method['fields']['deposit'];
                            foreach ($paymentMethods[$key]['deposit'] as $name => $paymentMethod) {
                                $paymentMethods[$key]['deposit'][$name]['currencies'] = [$currency];
                            }
                        }
                    }

                    if (isset($paymentMethods[$key]['withdraw'])) {
                        if (isset($method['fields']['withdraw']) && $method['fields']['withdraw'] !== [false]) {
                            foreach ($method['fields']['withdraw'] as $fieldName => $field) {
                                if (!isset($paymentMethods[$key]['withdraw'][$fieldName])) {
                                    $paymentMethods[$key]['withdraw'][$fieldName] = $field;
                                    foreach ($paymentMethods[$key]['withdraw'] as $name => $paymentMethod) {
                                        $paymentMethods[$key]['withdraw'][$name]['currencies'] = [$currency];
                                    }
                                } else {
                                    $paymentMethods[$key]['withdraw'][$fieldName]['currencies'][] = $currency;
                                    if ($paymentMethods[$key]['withdraw'][$fieldName]['regex'] != $field['regex']) {
                                        $errors[] = "Please fix this regex: $currency => $key => withdraw => $fieldName";
                                    }
                                }
                            }
                        }

                    } else {
                        if (isset($method['fields']['withdraw']) && $method['fields']['withdraw'] !== [false]) {
                            $paymentMethods[$key]['withdraw'] = $method['fields']['withdraw'];
                            foreach ($paymentMethods[$key]['withdraw'] as $name => $paymentMethod) {
                                $paymentMethods[$key]['withdraw'][$name]['currencies'] = [$currency];
                            }
                        }
                    }
                }
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        foreach ($paymentMethods as $method => $methodFields) {
            if (isset($methodFields['deposit'])) {
                $paymentMethods[$method]['deposit'] = self::_convertFields($methodFields['deposit'], $method, 'deposit', true);
            }

            if (isset($methodFields['withdraw'])) {
                $paymentMethods[$method]['withdraw'] = self::_convertFields($methodFields['withdraw'], $method, 'withdraw', true);
            }
        }

        return $paymentMethods;
    }

    /**
     * Validation transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    private static function _validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = self::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency][$method])) {
            return "Method '{$method}' does not exist";
        }

        $method = $currencies[$currency][$method];

        if (!isset($method[$way]) || !$method[$way]) {
            return "Payment system does not support '{$way}'";
        }

        if ($way === Payment::WAY_DEPOSIT) {
            if (isset($method['d_min']) && $method['d_min'] > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            if (isset($method['d_max']) && $method['d_max'] <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;

            if ((isset($method['d_min']) && $method['d_min'] > $amount) || (isset($method['d_max']) && $method['d_max'] <= $amount)) {
                return "Amount should to be more than '{$method['d_min']}' and less than '{$method['d_max']}'";
            }
        }

        if ($way === Payment::WAY_WITHDRAW) {
            if (isset($method['w_min']) && $method['w_min'] > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            if (isset($method['w_max']) && $method['w_max'] <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;

            if ((isset($method['w_min']) && $method['w_min'] > $amount) || (isset($method['w_max']) && $method['w_max'] <= $amount)) {
                return "Amount should to be more than '{$method['w_min']}' and less than '{$method['w_max']}'";
            }
        }

        return true;
    }

    /**
     * Checking final status for deposit
     * @param int $status
     * @return boolean
     */
    private static function _isFinalDepositStatus(int $status): bool
    {
        $statuses = [
            3 => true, // Success
            4 => true, // Canceled
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking final status for withdraw
     * @param int $status
     * @return boolean
     */
    private static function _isFinalWithdrawStatus(int $status): bool
    {
        $statuses = [
            1 => false, // New =  вывод получен и создан в системе (промежуточный статус), не финальный
            2 => false, // WaitingManualConfirmation = вывод ожидает ручного проведения на стороне Trio, не финальный
            3 => false, // PsProcessing вывод пытается провестись на стороне поставщика услуги, не финальный
            4 => false, // PsProcessingError = ошибка в проведении вывода на стороне поставщика услуги, не финальный статус, необходимо анализировать massage сообщение
            5 => true,  // Success = вывод успешно проведен на стороне поставщика услуги, финальный статус
            6 => true,  // Rejected = вывод отклонен на стороне поставщика услуги, финальный статус
            7 => false, // ManualConfirmed = вывод подтвержден на стороне системы Trio и отправлен на поставщика услуг, не финальный статус.
            8 => true,  // ManualCanceled = вывод отменен вручную на стороне системы Trio, финальный статус.
            9 => false,  // PsNetworkError =  сетевая ошибка при создании выплаты на стороне поставщика услуги, не финальный статус.
            10 => true,  // ManualRejected =  успешный вывод отменен вручную на стороне системы Trio, финальный статус.
        ];

        return $statuses[$status] ?? false;
    }

    private static function _changeWithdrawTransactionStatus($transaction, $status)
    {
        if (self::_isSuccessWithdrawStatus($status)) {
            $transaction->status = self::STATUS_SUCCESS;
        } else {
            if (in_array($status, [6, 8, 10])) {
                $transaction->status = self::STATUS_CANCEL;
            } else {
                $transaction->status = self::STATUS_PENDING;
            }
        }
    }

    /**
     * Checking success status for deposit
     * @param int $status
     * @return bool
     */
    private static function _isSuccessDepositStatus(int $status): bool
    {
        return $status == 3;
    }

    /**
     * Check success status for withdraw
     * @param int $status
     * @return bool
     */
    private static function _isSuccessWithdrawStatus(int $status): bool
    {
        return $status == 5;
    }

    /**
     * @param string $paymentMethod
     * @param string $currency
     * @return null|string
     */
    private static function _transformPaymentMethod(string $paymentMethod, string $currency)
    {
        $currencies = self::getSupportedCurrencies();

        return $currencies[$currency][$paymentMethod]['code'] ?? null;
    }

    /**
     * @param string $currency
     * @param string $paymentMethod
     * @return null
     */
    private static function _getPaymentMethodID(string $currency, string $paymentMethod)
    {
        $currencies = self::getSupportedCurrencies();

        return $currencies[$currency][$paymentMethod]['paymethod_id'] ?? null;
    }

    /**
     * @param string $currency
     * @param string $method
     * @return string
     */
    private static function _getPayway(string $currency, string $method): string
    {
        $currencies = self::getSupportedCurrencies();

        return $currencies[$currency][$method]['code'] ?? '';
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

        $replaceFields = $fields[$method][$way] ?? [];
        $replaceFields = $flip ? array_flip($replaceFields) : $replaceFields;

        $outFields = [];

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
        $methods = self::getApiFields();

        if (isset($methods[$method][$way])) {
            $fields = $methods[$method][$way];
            foreach (array_keys($fields) as $field) {
                if (!in_array($field, array_keys($requisites)) && in_array($currency, $fields[$field]['currencies'])) {
                    return "Required param '{$field}' not found";
                }

                if (isset($fields[$field]['regex']) && !preg_match("~{$fields[$field]['regex']}~", $requisites[$field] ?? '')) {
                    return "Invalid format of '{$field}'";
                }
            }
        }

        return true;
    }
}