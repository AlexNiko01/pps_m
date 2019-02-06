<?php

namespace pps\astropay;

use api\classes\ApiError;
use pps\querybuilder\QueryBuilder;
use Yii;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * Class Astropay
 * @package pps\astropay
 */
class Astropay extends Payment
{
    const CARD_BASE_URL = "https://api.astropaycard.com/";
    const CARD_SANDBOX_BASE_URL = "https://sandbox-api.astropaycard.com/";
    const BASE_URL = "https://api.astropay.com/";
    const SANDBOX_BASE_URL = "https://sandbox-api.astropay.com/";

    /**
     * @var object
     */
    private $_transaction;

    private $_x_login;
    private $_x_trans_key;
    private $_secret_key;
    private $_sandbox = true;

    private $_x_version = "2.0";
    private $_x_delim_char = "|";
    private $_x_test_request = "N";
    private $_x_duplicate_window = 60;
    private $_x_method = "CC";
    private $_x_response_format = "json";

    private $_card_api_url;
    private $_api_url;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['x_login'])) {
            throw new InvalidParamException('x_login empty');
        }
        if (empty($data['x_trans_key'])) {
            throw new InvalidParamException('x_trans_key empty');
        }
        if (empty($data['secret_key'])) {
            throw new InvalidParamException('secret_key empty');
        }

        $this->_sandbox = $data['sandbox'] ?? false;

        $this->_x_login = $data['x_login'];
        $this->_x_trans_key = $data['x_trans_key'];
        $this->_secret_key = $data['secret_key'];

        if ($this->_sandbox) {
            $this->_card_api_url = self::CARD_SANDBOX_BASE_URL;
            $this->_api_url = self::SANDBOX_BASE_URL;
        } else {
            $this->_card_api_url = self::CARD_BASE_URL;
            $this->_api_url = self::BASE_URL;
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function preInvoice(array $data): array
    {
        $validate = self::_validateTransaction($data['currency'], $data['payment_method'], Payment::WAY_DEPOSIT);

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

        $currencies = self::getSupportedCurrencies();

        $answer['data'] = [
            'fields' => $currencies[$data['currency']][$data['payment_method']]['fields'][Payment::WAY_DEPOSIT] ?? [],
            'currency' => 'RUB',
            'amount' => $data['currency'],
            'merchant_refund' => $data['amount'],
            'buyer_write_off' => null,
        ];

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

        $validate = self::_validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, Payment::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
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

        if (!empty($this->_transaction->id)) {
            $requests['m_out']->transaction_id = $this->_transaction->id;
            $requests['m_out']->data = $requests['merchant'];
            $requests['m_out']->type = 1;
            $requests['m_out']->save(false);
        }

        $query = [
            "x_login" => $this->_x_login,
            "x_tran_key" => $this->_x_trans_key,
            "x_amount" => $this->_transaction->amount,
            "x_currency" => 'RMB',//$this->_transaction->currency,
            "x_unique_id" => $this->_transaction->buyer_id,
            "x_version" => $this->_x_version,
            "x_test_request" => $this->_x_test_request,
            "x_duplicate_window" => $this->_x_duplicate_window,
            "x_method" => $this->_x_method,
            "x_invoice_num" => $this->_transaction->id,
            "x_delim_char" => $this->_x_delim_char,
            "x_response_format" => $this->_x_response_format,
            "x_type" => 'AUTH_ONLY',
        ];

        $requisites = json_decode($this->_transaction->requisites, true);

        $message = self::_checkRequisites($requisites, $this->_transaction->currency, $this->_transaction->payment_method, Payment::WAY_DEPOSIT);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $query['x_card_num'] = $requisites['card_number'];
        $query['x_card_code'] = $requisites['cvv'];
        $query['x_exp_date'] = $requisites['exp_month'] . '/' . $requisites['exp_year'];

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $request = (new QueryBuilder($this->_card_api_url . "verif/validator"))
            ->setParams($query)
            ->asPost()
            ->setOptions([
                CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
                CURLOPT_TIMEOUT => self::$timeout,
            ])
            ->send();

        $result = $request->getResponse(true);

        $this->_transaction->result_data = json_encode($result);

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-astropay-invoice');

        if (isset($result['response_code']) && $result['response_code'] == '1') {

            $this->_transaction->external_id = $result['TransactionID'];
            $this->_transaction->status = self::STATUS_CREATED;

            $redirect_url = rtrim(Yii::$app->params['api_link'], '/') . '/redirect/success/astropay';

            $answer['redirect'] = [
                'method' => 'GET',
                'url' => $redirect_url,
                'params' => [
                    'tr_id' => $this->_transaction->id
                ],
            ];

            $answer['data'] = $this->_transaction::getDepositAnswer($this->_transaction);

            $this->_transaction->save(false);

        } else {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save();

            $answer['status'] = 'error';

            if (isset($result['response_reason_text'])) {
                $answer['message'] = $result['response_reason_text'];
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
            }

            $message = "Request url = " . $this->_card_api_url . '/verif/validator';
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-astropay-invoice');
        }

        return $answer;
    }

    /**
     * TODO this method
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
            ], 'payment-astropay-receive');
            return false;
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        // If amounts not equal
        if ($this->_transaction->refund != $receiveData['shop_amount']) {
            $this->logAndDie('Astropay receive() transaction amount not equal received amount',
                "Transaction amount = {$this->_transaction->amount}\nreceived amount = {$receiveData['shop_amount']}",
                "Transaction amount not equal received amount");
        }

        // If different currency
        if ($data['currency_iso'] != $receiveData['shop_currency']) {
            $this->logAndDie('Astropay receive() different currency',
                "Merchant currency = {$data['currency_iso']}\nreceived currency = {$receiveData['shop_currency']}",
                "Different currency");
        }

        if ($this->_transaction->way === Payment::WAY_DEPOSIT) {

            if (self::_isSuccessDepositStatus($receiveData['status'])) {
                $this->_transaction->status = self::STATUS_SUCCESS;
            } else {
                if ($receiveData['status'] === 4) {
                    $this->_transaction->status = self::STATUS_CANCEL;
                }
            }
        } else {

            if (self::_isSuccessWithdrawStatus($receiveData['status'])) {
                $this->_transaction->status = self::STATUS_SUCCESS;
            } else {
                if (in_array($receiveData['status'], [6, 8])) {
                    $this->_transaction->status = self::STATUS_CANCEL;
                } else {
                    $this->_transaction->status = self::STATUS_PENDING;
                }
            }
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
        $validate = self::_validateTransaction($params['currency'], $params['payment_method'], Payment::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $currencies = self::getSupportedCurrencies();

        return [
            'data' => [
                'fields' => $currencies[$params['currency']][$params['payment_method']]['fields'][Payment::WAY_WITHDRAW] ?? [],
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'buyer_receive' => $params['amount'],
                'merchant_write_off' => null
            ]
        ];
    }

    /**
     * TODO this method
     * @param array $params
     * @return mixed
     */
    public function withDraw(array $params)
    {
        $this->_transaction = $params['transaction'];
        $requests = $params['requests'];

        if (!empty($this->_transaction->commission_payer)) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = self::_validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, Payment::WAY_WITHDRAW);

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
            'login' => $this->_x_login,
            'pass' => $this->_x_trans_key,
            'cashout_type' => 'ASTROPAYCARD',
            'external_id' => $this->_transaction->id,
            'amount' => $this->_transaction->amount,
            'currency' => $this->_transaction->currency,
            'window' => $this->_x_duplicate_window,
            'notification_url' => $params['callback_url'],
        ];

        $requisites = json_decode($this->_transaction->requisites, true);

        $message = self::_checkRequisites($requisites, $this->_transaction->currency, $this->_transaction->payment_method, Payment::WAY_WITHDRAW);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $query['document_id'] = $requisites['document_id'];
        $query['beneficiary_name'] = $requisites['name'];
        $query['beneficiary_lastname'] = $requisites['lastname'];
        $query['country'] = $requisites['country'];
        $query['email'] = $requisites['email'];

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $request = (new QueryBuilder($this->_api_url . "v3/cashout"))
            ->setParams($query)
            ->asPost()
            ->json(true)
            ->setHeader('Signature', $this->_getSign($query))
            ->setOptions([
                CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
                CURLOPT_TIMEOUT => self::$timeout,
            ])
            ->send();

        $result = $request->getResponse();

        $this->_transaction->result_data = json_encode($result);

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result, 'info' => $request->getInfo()], 'payment-astropay-withdraw');

        $answer = [];

        if (isset($result['cashout_id'])) {

            $this->_transaction->external_id = $result['cashout_id'];
            $this->_transaction->receive = $this->_transaction->amount;
            $this->_transaction->status = Payment::STATUS_CREATED;
            $this->_transaction->save(false);

            $answer['data']['id'] = $this->_transaction->id;
            $answer['data']['transaction_id'] = $this->_transaction->merchant_transaction_id;
            $answer['data']['status'] = $this->_transaction->status;
            $answer['data']['buyer_receive'] = $this->_transaction->receive;
            $answer['data']['amount'] = $this->_transaction->amount;
            $answer['data']['currency'] = $this->_transaction->currency;
        } else if ($request->getErrno() == CURLE_OPERATION_TIMEOUTED) {
            $this->_transaction->status = self::STATUS_TIMEOUT;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } else if ($request->getErrno() != CURLE_OK) {
            $this->_transaction->status = self::STATUS_NETWORK_ERROR;
            $this->_transaction->result_data = $request->getErrno();
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } else {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save();

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
     * TODO this method
     * Updating not final statuses
     * @param object $transaction
     * @param null|object $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        $this->_transaction = $transaction;

        if (in_array($transaction->status, Payment::getNotFinalStatuses())) {

            $query = [
                'x_login' => $this->_x_login,
                'x_trans_key' => $this->_x_trans_key,
                'x_invoice_num' => $transaction->merchant_transaction_id,
                'x_delim_char' => $this->_x_delim_char,
                'x_test_request' => $this->_x_test_request,
                'x_response_format' => $this->_x_response_format,
                'x_type' => 0,
            ];

            $request = (new QueryBuilder($this->_card_api_url . "verif/transtatus"))
                ->setParams($query)
                ->asPost()
                ->send();

            $response = $request->getResponse(true);

            Yii::info(['query' => $query, 'res' => $response], 'payment-astropay-status');

            if (isset($model_req)) {
                // Saving the data that came from the PA in the unchanged state
                $model_req->transaction_id = $this->_transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            if (isset($response['result']) && $response['result'] === 'ok') {

                if (isset($response['data']['status'])) {
                    if (self::_isSuccessWithdrawStatus($response['data']['status'])) {
                        $this->_transaction->status = self::STATUS_SUCCESS;
                    } else {
                        if (in_array($response['data']['status'], [6, 8])) {
                            $this->_transaction->status = self::STATUS_CANCEL;
                        } else {
                            $this->_transaction->status = self::STATUS_PENDING;
                        }
                    }
                }

                $this->_transaction->save(false);

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
        $headers = Yii::$app->request->getHeaders();
        $sign = $headers->get('X-Api-Signature');

        $expectedSign = $this->_getSign($params);
        // If sign is wrong
        if ($sign != $expectedSign) {
            Yii::error("Astropay receive() wrong sign is received: expectedSign = {$expectedSign}\nSign = {$sign}", 'payment-astropay-receive');
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
        $requestPayload = json_encode($dataSet);
        return hash_hmac('sha256', $requestPayload, $this->_secret_key, false);
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
        return isset($data['tr_id']) ? ['id' => $data['tr_id']]: [];
    }

    /**
     * TODO this method
     * Getting transaction id.
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['tr_id'] ?? 0;
    }

    /**
     * Getting success answer for stopping send callback data
     * @return array
     */
    public static function getSuccessAnswer()
    {
        return [
            'result' => [
                'result_code' => 0
            ]
        ];
    }

    /**
     * Getting response format for success answer
     * @return string
     */
    public static function getResponseFormat()
    {
        return Response::FORMAT_XML;
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

        return $paymentMethods;
    }

    /**
     * Validation transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return bool|string
     */
    private static function _validateTransaction(string $currency, string $method, string $way)
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

        return true;
    }

    /**
     * Checking final status
     * @param int $status
     * @return boolean
     */
    private static function _isFinalStatus(int $status): bool
    {
        $statuses = [
            'WAITING' => false,
            'SUCCESS' => true,
            'ERROR' => true
        ];

        return $statuses[$status] ?? false;
    }

    private static function _isFinalDepositStatus($status): bool
    {
        $statuses = [
            'waiting' => false,
            'paid' => true,
            'rejected' => true,
            'unpaid' => true,
            'expired' => true,
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking success status
     * @param int $status
     * @return bool
     */
    private static function _isSuccessStatus($status): bool
    {
        return $status === 'SUCCESS';
    }

    /**
     * Checking success status
     * @param int $status
     * @return bool
     */
    private static function _isSuccessDepositStatus($status): bool
    {
        return $status === 'paid';
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
            foreach (array_keys($methods[$method][$way]) as $field) {
                if (!in_array($field, array_keys($requisites)) && in_array($currency, $methods[$method][$way][$field]['currencies'])) {
                    return "Required param '{$field}' not found";
                }

                if (!preg_match("~{$methods[$method][$way][$field]['regex']}~", $requisites[$field] ?? '')) {
                    return "Invalid format of param '{$field}'";
                }
            }
        }

        return true;
    }
}