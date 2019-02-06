<?php

namespace pps\royalpay;

use api\classes\ApiError;
use pps\payment\TypeFactory;
use Yii;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * Class RoyalPay
 * @package pps\royalpay
 */
class RoyalPay extends Payment
{
    const API_URL = 'https://royal-pay.com/api';

    /**
     * @var string
     */
    private $_auth_key;
    /**
     * @var string
     */
    private $_secret_key;
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
        if (!$data['auth_key']) {
            throw new InvalidParamException('auth_key empty');
        }
        if (!$data['secret_key']) {
            throw new InvalidParamException('secret_key empty');
        }

        $this->_auth_key = $data['auth_key'];
        $this->_secret_key = $data['secret_key'];
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $data
     * @return array
     */
    public function preInvoice(array $data): array
    {
        $validate = static::validateTransaction($data['currency'], $data['payment_method'], $data['amount'], Payment::WAY_DEPOSIT);

        if (!empty($data['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $fields = static::_getFields($data['currency'], $data['payment_method'], Payment::WAY_DEPOSIT);
        $newFields = static::_convertFields($fields, $data['payment_method'], Payment::WAY_DEPOSIT, true);

        return [
            'data' => [
                'fields' => $newFields,
                'currency' => $data['currency'],
                'amount' => $data['amount'],
                'buyer_write_off' => null,
                'merchant_refund' => null,
            ]
        ];
    }

    /**
     * Invoicing for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        if (!empty($transaction->commission_payer)) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $payment_method = static::_transformPaymentMethod($transaction->payment_method, $transaction->currency);

        $validate = static::validateTransaction($transaction['currency'], $transaction->payment_method, $transaction['amount'], Payment::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $answer = [];

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_TRANSACTION_ID
            ];
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $query = [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'payment_system' => $payment_method,
            'note' => $transaction->comment,
            'url' => [
                'callback_url' => $params['callback_url']
            ]
        ];

        $requisitesOrigin = json_decode($transaction->requisites, true);

        $message = self::_checkRequisites($requisitesOrigin, $transaction->currency, $transaction->payment_method, Payment::WAY_DEPOSIT);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $requisites = self::_convertFields($requisitesOrigin, $transaction->payment_method, Payment::WAY_DEPOSIT);

        $hidden = static::_getFields($transaction->currency, $transaction->payment_method, 'hidden');

        $query['system_fields'] = $this->_modifyHiddenValues($transaction, $hidden, $params);

        if (!empty($requisites)) {
            $query['system_fields'] = array_merge($query['system_fields'], $requisites);
        }

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $result = $this->_query('deposit/create', $query);

        $transaction->result_data = json_encode($result);

        $this->logger->log($transaction->id, 2, $transaction->result_data);

        Yii::info(['query' => $query, 'result' => $result], 'payment-royal-pay-invoice');

        if (isset($result['status']) && $result['status'] === 'created') {

            $transaction->refund = $result['amount_client'];
            $transaction->external_id = $result['id'];
            $transaction->write_off = $result['amount_to_pay'];
            $transaction->status = self::STATUS_CREATED;
            $transaction->save(false);

            $answer['redirect']['method'] = $result['redirect']['method'];
            $answer['redirect']['url'] = $result['redirect']['url'];
            $answer['redirect']['params'] = $result['redirect']['params'];

            $answer['data'] = $transaction::getDepositAnswer($transaction);

        } else if ($this->_query_errno != CURLE_OK) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;

        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save();

            $answer['status'] = 'error';

            if (isset($result['message'])) {
                $answer['message'] = json_encode($result['message']);
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
            }
        }

        return $answer;
    }

    /**
     * Check if the seller has enough money.
     * Getting required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $data
     * @return array
     */
    public function preWithDraw(array $data): array
    {
        $validate = self::validateTransaction($data['currency'], $data['payment_method'], $data['amount'], Payment::WAY_WITHDRAW);

        if (!empty($data['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $fields = self::_getFields($data['currency'], $data['payment_method'], Payment::WAY_WITHDRAW);
        $newFields = static::_convertFields($fields, $data['payment_method'], Payment::WAY_WITHDRAW, true);

        return [
            'data' => [
                'fields' => $newFields,
                'currency' => $data['currency'],
                'amount' => $data['amount'],
                'merchant_write_off' => null,
                'buyer_receive' => null,
            ]
        ];
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params): array
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        if (!empty($transaction->commission_payer)) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $payment_method = static::_transformPaymentMethod($transaction->payment_method, $transaction->currency);

        $validate = static::validateTransaction($transaction->currency, $transaction->payment_method, $transaction->amount, Payment::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $requisitesOrigin = json_decode($transaction->requisites, true);

        $message = self::_checkRequisites($requisitesOrigin, $transaction->currency, $transaction->payment_method, Payment::WAY_DEPOSIT);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_TRANSACTION_ID
            ];
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $query = [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'payment_system' => $payment_method,
            'url' => [
                'callback_url' => $params['callback_url']
            ]
        ];

        $requisites = self::_convertFields($requisitesOrigin, $transaction->payment_method, Payment::WAY_WITHDRAW);

        if (!empty($requisites)) {

            foreach ($requisites as $key => $fields) {
                $requisites[$key] = urldecode($fields);
            }

            $query['system_fields'] = $requisites;
        }

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $result = $this->_query('deduce/create', $query);

        $transaction->result_data = json_encode($result);

        $this->logger->log($transaction->id, 3, $transaction->result_data);

        Yii::info(['query' => $query, 'result' => $result], 'payment-royal-pay-withdraw');

        if (isset($result['status']) && $result['status'] === 'created') {
            $transaction->external_id = $result['id'];
            $transaction->receive = $result['amount_to_pay'];
            $transaction->write_off = $result['amount_client'];

            if (empty($transaction->status)) {
                $transaction->status = Payment::STATUS_CREATED;
            }

            $transaction->save(false);

            $answer = [
                'data' => $transaction::getWithdrawAnswer($transaction)
            ];

        } else if ($this->_query_errno == CURLE_OPERATION_TIMEOUTED) {
            $transaction->status = self::STATUS_TIMEOUT;
            $transaction->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else if ($this->_query_errno != CURLE_OK) {
            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->result_data = $this->_query_errno;
            $transaction->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save();

            $answer = ['status' => 'error'];

            if (isset($result['message'])) {
                $answer['message'] = $result['message'];
            } else if (isset($result['code'])) {
                $answer['message'] = self::_getError($result['code']);
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
            }
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

        if (in_array($transaction->status, Payment::getFinalStatuses())) {
            return true;
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        $need = ['status', 'amount_client', 'amount_payed', 'currency'];

        if (!self::checkRequiredParams($need, $receiveData)) {
            Yii::error([
                'receive_data' => $receiveData,
                'need' => $need
            ], 'payment-royal-pay-receive');
            return false;
        }

        if (empty($transaction->external_id)) {
            $transaction->external_id = $receiveData['id'];
            $transaction->save(false);
        }

        $amount = (float)$transaction->amount;
        $write_off = (float)$transaction->write_off;

        $tid = $receiveData['transaction_id'] ?? 'undefined';

        if ($transaction->way === self::WAY_WITHDRAW) {
            $receiveAmount = (float)$receiveData['amount_payed'];
            // If amounts not equal
            if ($write_off != $receiveAmount) {
                Yii::error([
                    'title' => "Royal Pay receive() withdraw transaction amount not equal received amount",
                    'transaction_id' => $tid,
                    'message' => "Transaction amount = {$write_off}\nreceived amount = {$receiveAmount}",
                ], 'payment-royal-pay-receive');
                die("Transaction amount not equal received amount");
            }
        } else {
            $receiveAmount = (float)$receiveData['amount_payed'];
            // If amounts not equal
            if ($write_off != $receiveAmount) {
                Yii::error([
                    'title' => "Royal Pay receive() deposit transaction amount not equal received amount",
                    'transaction_id' => $tid,
                    'message' => "Transaction amount = {$write_off}\nreceived amount = {$receiveAmount}",
                ], 'payment-royal-pay-receive');
                die("Transaction amount not equal received amount");
            }
        }

        // If different currency
        if ($data['currency'] != $receiveData['currency']) {
            Yii::error([
                'title' => "Royal Pay receive() different currency",
                'transaction_id' => $tid,
                'message' => "Merchant currency = {$data['currency']}\nreceived currency = {$receiveData['currency']}",
            ], 'payment-royal-pay-receive');
            die("Different currency");
        }

        // If status not success
        if ($receiveData['status'] !== 'ok') {
            if ($receiveData['status'] === 'error') {
                $transaction->status = self::STATUS_ERROR;
            }

            if ($receiveData['status'] === 'cancel') {
                $transaction->status = self::STATUS_CANCEL;
            }
        } else {
            $transaction->status = self::STATUS_SUCCESS;
        }

        $factory = new TypeFactory($receiveData['system_fields'] ?? []);
        $data = $factory->getInstance($transaction->payment_method, self::_getKeysForRequisites($transaction->payment_method));
        $transaction->callback_data = json_encode([$transaction->payment_method => $data->getFieldsWithUndefined()]);

        $transaction->save(false);

        return true;
    }

    /**
     * Updating not final statuses
     * @param object $transaction
     * @param null|object $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if (in_array($transaction->status, Payment::getNotFinalStatuses())) {

            if (empty($transaction->external_id)) {
                $response = $this->_query('status/merchant', [
                    'id' => $transaction->id
                ], false);
            } else {
                $response = $this->_query('status/' . $transaction->external_id, [], false);
            }

            $this->logger->log($transaction->id, 8, $response);

            Yii::info($response, 'payment-royal-pay');

            if (isset($response['status'])) {

                if (static::_isFinal($response['status'])) {
                    if (static::_isSuccessStateCustom($response['status'])) {
                        $transaction->status = self::STATUS_SUCCESS;
                    } else {
                        switch ($response['status']) {
                            case 'cancel':
                                $transaction->status = self::STATUS_CANCEL;
                                break;
                            case 'error':
                                $transaction->status = self::STATUS_ERROR;
                                break;
                        }
                    }
                } else {
                    $transaction->status = self::STATUS_PENDING;
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
        $headers = Yii::$app->request->headers;
        $auth = $headers->get('Auth');
        $sign = $headers->get('Sign');

        $signCheckArray = array_filter($params, function ($v) {
            return $v !== null && $v !== '';
        });

        $expectedSign = $this->_getSign($signCheckArray);

        if ($sign != $expectedSign && $auth != $this->_auth_key) {
            Yii::error("Royal Pay receive() wrong sign or auth is received: expectedSign = $expectedSign \nSign = $sign, expectedAuth: $this->_auth_key\n Auth: $auth", 'payment-royal-pay');
        } else {
            return true;
        }

        return false;
    }

    /**
     * Main query method
     * @param string $url
     * @param array $params
     * @param bool $post
     * @return boolean|object
     */
    private function _query(string $url, array $params = [], $post = true)
    {
        $url = self::API_URL . '/' . trim($url, '/');

        $sign = $this->_getSign($params);

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
            CURLOPT_POST => $post,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Auth: $this->_auth_key",
                "Sign: $sign"
            ]
        ]);

        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        $response = curl_exec($ch);
        $this->_query_info = curl_getinfo($ch);
        $this->_query_errno = curl_errno($ch);

        curl_close($ch);

        if ($response === false) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    /**
     * Get sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        $signString = json_encode($dataSet);
        $signString .= $this->_secret_key;

        return md5($signString);
    }

    /**
     * Modified hidden values for sending query
     * @param $transaction
     * @param array $values
     * @param array $params
     * @return array
     */
    private function _modifyHiddenValues($transaction, array $values, $params = [])
    {
        $date = new \DateTime('now');
        $date->modify('+1 day');

        $hiddenValues = [
            'comment' => $transaction->comment,
            'success_url' => isset($params['success_url']) ? $params['success_url'] . '?txid=' . $transaction->id : '',
            'fail_url' => isset($params['fail_url']) ? $params['fail_url'] . '?txid=' . $transaction->id : '',
            'success_url_method' => 'get',
            'fail_url_method' => 'get',
            'lifetime' => $date->format('Y-m-d\TH-i-s')
        ];

        array_walk($values, function ($item, $key) use (&$values, $hiddenValues) {
            $values[$key] = $hiddenValues[$item] ?? '';
        });

        return $values;
    }

    /**
     * Getting supported currencies and payment methods
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return require(__DIR__ . '/currency_lib.php');
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
    static function validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

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

        if (0 >= $amount) {
            self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            return "Amount should to be more than '0'";
        }

        return true;
    }

    /**
     * Getting fields for filling
     * @param string $currency
     * @param string $payment_method
     * @param string $way
     * @return array
     */
    private static function _getFields(string $currency, string $payment_method, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$payment_method]['fields'][$way] ?? [];
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
     * Getting query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        $query = [];
        if (isset($data['order'])) {
            $query = ['external_id' => $data['order']];
        }
        if (isset($data['txid'])) {
            $query = ['id' => $data['txid']];
        }
        return $query;
    }

    /**
     * Getting transaction id
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['transaction_id'] ?? 0;
    }

    /**
     * Getting success answer for stopping send callback data
     * @return array
     */
    public static function getSuccessAnswer()
    {
        return ['answer' => 'ok'];
    }

    /**
     * Getting response format for success answer
     * @return string
     */
    public static function getResponseFormat()
    {
        return Response::FORMAT_JSON;
    }

    /**
     * Transforming payment method to payment system method
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
     * Get payment system local errors
     * @param int $code
     * @return string
     */
    private static function _getError(int $code): string
    {
        $type = substr($code, 0, 1) . '00';

        $errorTypes = [
            100 => 'Authorization errors, request format',
            200 => 'Query content errors',
            300 => 'Data Validation Errors',
            400 => 'Errors of the payment system',
            500 => 'Royal Pay server errors'
        ];

        $errors = [
            '101' => 'Merchant not found',
            '102' => 'Invalid request signature',
            '103' => 'Required request header not found',
            '104' => 'Invalid request format / incorrectly structured by JSON',

            '201' => 'Required field not found',
            '202' => 'Payment system not found or available',
            '203' => 'The payment system does not support this type of transaction (deposit or withdraw)',

            '301' => 'The field has an incorrect format',
            '302' => 'Transaction not found',
            '303' => 'After calculating the commission, the amount is less than 0',

            '401' => 'Error on the side of the payment system, the message field will contain more detailed information',

            '501' => 'An error occurred on the side of the Royal Pay server, the message field will contain more detailed information'
        ];

        return "{$errorTypes[$type]}. {$errors[$code]}";
    }

    /**
     * Checking final status
     * @param string $status
     * @return bool
     */
    private static function _isFinal(string $status): bool
    {
        $statuses = [
            'ok' => true,
            'pending' => false,
            'cancel' => true,
            'error' => true
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking success status
     * @param string $status
     * @return bool
     */
    private static function _isSuccessStateCustom(string $status): bool
    {
        return $status === 'ok';
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
                /*if (isset($fields[$field]['regex']) && !preg_match("~{$fields[$field]['regex']}~", $requisites[$field] ?? '')) {
                    return "Invalid format of '{$field}'";
                }*/
            }
        }

        return true;
    }

    /**
     * @param string $method
     * @return array
     */
    private static function _getKeysForRequisites(string $method): array
    {
        $keys = [
            'qiwi' => [
                "qiwi_wallet" => "phone",
            ],
            'adv-cash' => [

            ]
        ];

        return $keys[$method] ?? [];
    }
}