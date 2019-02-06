<?php

namespace pps\fondy;

use api\classes\ApiError;
use common\models\Transaction;
use Yii;
use pps\payment\Payment;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class Fondy
 * @package pps\fondy
 */
class Fondy extends Payment
{
    const API_URL = 'https://api.fondy.eu/api';

    /**
     * @var integer
     */
    private $_merchant_id;
    /**
     * @var string
     */
    private $_password_pay;
    /**
     * @var string
     */
    private $_password_credit;

    /** @var int */
    private $_errno;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['merchant_id'])) throw new InvalidParamException('merchant_id empty');
        if (empty($data['password_pay'])) throw new InvalidParamException('pay password empty');
        if (empty($data['password_credit'])) throw new InvalidParamException('credit password empty');

        $this->_merchant_id = $data['merchant_id'];
        $this->_password_pay = $data['password_pay'];
        $this->_password_credit = $data['password_credit'];
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $validate = static::validateTransaction($params['currency'], floatval($params['amount']), self::WAY_DEPOSIT);

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

        return [
            'data' => [
                'fields' => static::getFields($params['currency'], self::WAY_DEPOSIT),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
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
        /**
         * @var $transaction Transaction
         */
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID,
            ];
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $validate = static::validateTransaction($transaction->currency, $transaction->amount, self::WAY_DEPOSIT);

        if ($validate !== true) {
            $return = [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];

            $this->logger->log($transaction->id, 100, $return);

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return $return;
        }

        if (!empty($params['commission_payer'])) {
            $return = [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];

            $this->logger->log($transaction->id, 100, $return);

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return $return;
        }

        $requisites = json_decode($transaction->requisites, true);

        $message = static::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_DEPOSIT);

        if ($message !== true) {
            $return = [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];

            $this->logger->log($transaction->id, 100, $return);

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return $return;
        }

        $query = [
            'order_id' => $transaction->id,
            'merchant_id' => $this->_merchant_id,
            'order_desc' => $transaction->comment ?? self::WAY_DEPOSIT,
            'currency' => $transaction->currency,
            'amount' => $transaction->amount * 100,
        ];

        $query['server_callback_url'] = $params['callback_url'];
        $query['signature'] = $this->_getSign($query, $this->_password_pay);

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        Yii::info([
            'query' => $query,
            'result' => []
        ],
            'payment-fondy-invoice'
        );

        $transaction->refund = $transaction->amount;
        $transaction->status = self::STATUS_CREATED;
        $transaction->save(false);

        $answer = [
            'redirect' => [
                'method' => 'POST',
                'url' => static::API_URL . '/checkout/redirect/',
                'params' => $query,
            ],
            'data' => $transaction::getDepositAnswer($transaction)
        ];

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
        $receive = $data['receive_data'];

        if (in_array($transaction->status, self::getFinalStatuses())) {
            return true;
        }

        if (!isset($receive['signature'])) {
            Yii::error(
                "Fondy receive() signature is not set: transaction received signature is not set!",
                'payment-fondy-receive'
            );

            return false;
        }

        if (!static::checkReceivedSign($receive)) {
            Yii::error(
                "Fondy receive() wrong sign is received.",
                'payment-fondy-receive'
            );

            return false;
        }

        if ($receive['order_id'] != $transaction->id) {
            Yii::error(
                "Fondy receive() transaction id is not equal!",
                'payment-fondy-receive'
            );

            return false;
        }

        /*if ($receive['response_status'] == Status::RESPONSE_STATUS_FAILURE) {
            Yii::error(
                "Fondy receive() response status is FAIL.\nCode = {$receive['error_code']}\nMessage = {$receive['error_message']}\n",
                'payment-fondy-receive'
            );

            return false;
        }*/

        if (floatval($transaction->amount) != floatval($receive['amount'] / 100)) {
            Yii::error(
                "Fondy receive() transaction amount not equal received amount.\nTransaction amount = {$transaction->amount}\nreceived amount = " . floatval($receive['amount'] / 100),
                'payment-fondy-receive'
            );

            return false;
        }

        if ($transaction->currency != $receive['currency']) {
            Yii::error(
                "Fondy receive() different currency.\nMerchant currency = {$transaction->currency}\nreceived currency = {$receive['currency']}",
                'payment-fondy-receive'
            );

            return false;
        }

        static::setStatus($transaction, $receive['order_status']);

        $transaction->save(false);

        if (!static::isFinal($receive['order_status'])) {
            if (array_key_exists('updateStatusJob', $data)) {
                $data['updateStatusJob']->transaction_id = $transaction->id;
            }
        }

        $ps_data = [];
        
        if (!empty($receive['masked_card'])) {
            $ps_data['masked_card'] = $receive['masked_card'];
        }
        if (!empty($receive['sender_email'])) {
            $ps_data['sender_email'] = $receive['sender_email'];
        }

        if (!empty($ps_data)) {
            $ps_fields = static::getCallbackFields();
            $callback_data = [];
            $count = 0;

            foreach ($ps_data as $field => $value) {
                $ps_field = 'undefined_' . ++$count;
                if (isset($ps_fields[$field])) {
                    $ps_field = ($ps_fields[$field] != '-') ? $ps_fields[$field] : null;
                }

                if (isset($ps_field)) {
                    $callback_data[$transaction->payment_method][$ps_field] = $value;
                }
            }

            $transaction->callback_data = json_encode($callback_data);
        }

        if (isset($receive['payment_id'])) {
            $transaction->external_id = $receive['payment_id'];
        }

        $transaction->save(false);

        return true;
    }

    /**
     * Check if the seller has enough money.
     * Getting required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        $validate = self::validateTransaction($params['currency'], $params['amount'], self::WAY_WITHDRAW);

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

        return [
            'data' => [
                'fields' => static::getFields($params['currency'], self::WAY_WITHDRAW),
                'currency' => $params['currency'],
                'amount' => round($params['amount'], 2),
                'merchant_write_off' => null,
                'buyer_receive' => null
            ]
        ];
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params)
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];
        $url = static::API_URL . '/p2pcredit/';
        $answer = [];

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $validate = $this->validateTransaction($transaction->currency, $transaction->amount, self::WAY_WITHDRAW);

        if ($validate !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $return = [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];

            $this->logger->log($transaction->id, 100, $return);

            return $return;
        }

        $query = [
            'order_id' => $transaction->id,
            'order_desc' => $transaction->comment,
            'currency' => $params['currency'],
            'amount' => intval($transaction->amount * 100),
            'merchant_id' => $this->_merchant_id,
        ];

        $requisites = json_decode($transaction->requisites, true);

        $message = static::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW);

        if ($message !== true) {
            $return = [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];

            $this->logger->log($transaction->id, 100, $return);

            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return $return;
        }

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites);
        }

        $query['signature'] = $this->_getSign($query, $this->_password_credit);

        $transaction->query_data = json_encode($query);
        $transaction->save(false);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $response = $this->_query($url, ['request' => $query]);

        $transaction->result_data = json_encode($response);
        $transaction->save(false);

        $this->logger->log($transaction->id, 3, $transaction->result_data);

        Yii::info([
            'query' => $query,
            'result' => $response
        ],
            'payment-fondy-withdraw'
        );

        $response = $response['response'];

        if (!isset($response['error_message'])) {
            $transaction->external_id = $response['payment_id'] ?? null;
            $transaction->receive = floatval($response['amount'] / 100) ?? null;
            $transaction->write_off = floatval($response['amount'] / 100) ?? null;

            static::setStatus($transaction, $response['order_status']);

            $transaction->save(false);

            $answer['data'] = [
                'id' => $transaction->id,
                'transaction_id' => $transaction->merchant_transaction_id,
                'status' => $transaction->status,
                'buyer_receive' => $transaction->receive,
                'amount' => round($transaction->amount, 2),
                'currency' => $transaction->currency,
            ];

            if (!in_array($transaction->status, self::getFinalStatuses())) {
                $params['updateStatusJob']->transaction_id = $transaction->id;
            }
        } elseif ($this->_errno == CURLE_OPERATION_TIMEOUTED) {
            $transaction->status = self::STATUS_TIMEOUT;
            $transaction->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } elseif ($this->_errno > CURLE_OK) {
            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->save(false);

            $this->logger->log($transaction->id, 100, $response);
        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer = [
                'status' => 'error',
                'message' => $response['error_message'] ?? self::ERROR_OCCURRED,
            ];

            $this->logger->log($transaction->id, 100, $answer);
        }

        return $answer;
    }

    /**
     * @param Transaction $transaction
     * @param null $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        $url = static::API_URL . '/status/order_id';

        if (in_array($transaction->status, self::getNotFinalStatuses())) {
            $query = [
                'order_id' => $transaction->id,
                'merchant_id' => $this->_merchant_id,
            ];

            $query['signature'] = $this->_getSign($query, $this->_password_pay);

            $response = $this->_query($url, $query);

            Yii::info([
                'query' => $query,
                'res' => $response
            ],
                'payment-fondy-status'
            );

            $this->logger->log($transaction->id, 8, $response);

            if (empty($transaction->external_id) && isset($response['payment_id'])
            ) {
                $transaction->external_id = $response['payment_id'];
            }

            if (isset($response['order_status'])) {
                static::setStatus($transaction, $response['order_status']);
            }

            $transaction->save(false);

            return true;
        }

        return false;
    }

    /**
     * Main query method
     * @param string $url
     * @param array $params
     * @return array
     */
    private function _query(string $url, array $params)
    {
        Yii::info([
            'url' => $url,
            'params' => $params
        ], 'payment-fondy-query-params');

        $query = json_encode($params);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
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
                'Content-Length: ' . strlen($query)
            ],
        ]);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        $this->_errno = curl_errno($ch);

        Yii::info([
            'response' => $response,
            'curl_info' => $info,
            'curl_errno' => $this->_errno,
            'curl_error' => $error,
        ], 'payment-fondy-query-response');

        curl_close($ch);

        if ($response !== false) {
            return json_decode($response, true);
        }

        return $response;
    }

    /**
     * Getting supported currencies
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
     * Getting query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return [
            'id' => $data['order_id']
        ];
    }

    /**
     * Getting transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['order_id'] ?? 0;
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

        foreach ($currencies as $currency => $methods) {
            foreach ($methods as $key => $method) {
                if ($method['deposit'] && !isset($method['fields']['deposit'])) {
                    $method['fields']['deposit'] = [];
                }
                $currencies[$currency][$key] = $method['fields'];
            }
        }

        return $currencies;
    }

    /**
     * Sign function
     * @param array $params
     * @param $password
     * @return string
     * @internal param $string
     */
    private function _getSign(array $params = [], $password): string
    {
        $params = array_filter($params, 'strlen');

        ksort($params);
        $params = array_values($params);

        array_unshift($params, $password);

        return (sha1(join('|', $params)));
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
     * Validation transaction before send to payment system
     * @param string $currency
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    private static function validateTransaction(string $currency, float $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        $curr_method = $currencies[$currency]['fondy'];

        if (!isset($curr_method[$way]) || !$curr_method[$way]) {
            return "Payment system does not supports '{$way}'";
        }

        if (0 >= $amount) {
            self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            return "Amount should be more than '0'";
        }

        return true;
    }

    /**
     * @param array $params
     * @return bool
     */
    private function checkReceivedSign(array $params): bool
    {
        $sign = $params["signature"];

        if (array_key_exists('response_signature_string', $params)) {
            unset($params['response_signature_string']);
        }

        unset($params['signature']);

        $expectedSign = $this->_getSign($params, $this->_password_pay);

        if ($expectedSign != rawurldecode($sign)) {
            Yii::error("Fondy receive() wrong sign or auth is received: expectedSign = $expectedSign \nSign = $sign", 'payment-fondy');

            return false;
        }

        return true;
    }

    /**
     * @param Transaction $transaction
     * @param $status
     */
    public static function setStatus($transaction, $status)
    {
        switch ($status) {
            case Status::ORDER_STATUS_APPROVED:
                $transaction->status = self::STATUS_SUCCESS;
                break;
            case Status::ORDER_STATUS_DECLINED:
                $transaction->status = self::STATUS_CANCEL;
                break;
            case Status::ORDER_STATUS_REVERSED:
                $transaction->status = self::STATUS_VOIDED;
                break;
            case Status::ORDER_STATUS_REFUNDED:
                $transaction->status = self::STATUS_REFUNDED;
                break;
            case Status::ORDER_STATUS_CREATED:
            case Status::ORDER_STATUS_PROCESSING:
                $transaction->status = self::STATUS_PENDING;
                break;
                
        }
    }

    /**
     * Checking final status
     * @param string $status
     * @return bool
     */
    private static function isFinal(string $status): bool
    {
        $statuses = [
            Status::ORDER_STATUS_APPROVED,
            Status::ORDER_STATUS_DECLINED,
            Status::ORDER_STATUS_REFUNDED,
            Status::ORDER_STATUS_REVERSED,
        ];

        return in_array($status, $statuses);
    }

    /**
     * Getting callback fields
     * @param string $payment_method
     * @return array
     */
    private static function getCallbackFields(string $payment_method = 'fondy'): array
    {
        $data = [
            'fondy' => [
                'masked_card' => 'number',
                'sender_email' => 'email',
            ],
        ];

        return $data[$payment_method] ?? [];
    }

    /**
     * Getting fields for filling
     * @param string $currency
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency]['fondy']['fields'][$way] ?? [];
    }
}
