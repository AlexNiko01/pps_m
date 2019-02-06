<?php

namespace pps\cryptonator;

use api\classes\ApiError;
use Yii;
use pps\payment\Payment;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class Cryptonator
 * @package pps\cryptonator
 */
class Cryptonator extends Payment
{
    const API_URL = 'https://api.cryptonator.com/api/merchant/v1';
    const INVOICE_URL = 'https://www.cryptonator.com/merchant/invoice';

    /**
     * @var string
     */
    private $_merchant_id;

    /**
     * @var string
     */
    private $_secret_key;
    /** @var int */
    private $_query_errno;
    /** @var array */
    private $_query_info = [];


    /**
     * Fill the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['secret_key'])) {
            throw new InvalidParamException('secret_key empty');
        }

        if (empty($data['merchant_id'])) {
            throw new InvalidParamException('merchant_id empty');
        }

        $this->_secret_key = $data['secret_key'];
        $this->_merchant_id = $data['merchant_id'];
    }

    /**
     * Preliminary calculation of the invoice.
     * Get required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $coefficient = self::getCoefficient($params['currency']);
        $amount = $params['amount'] / $coefficient;

        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $amount, self::WAY_DEPOSIT);

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
                'merchant_refund' => $params['amount'],
                //'commission' => static::getCommission($params['currency'], $amount),
                'buyer_write_off' => null,
            ]
        ];
    }

    /**
     * Invoice for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        $coefficient = self::getCoefficient($params['currency']);
        $transaction->amount = $transaction->amount / $coefficient;

        $payment_method = static::transformPaymentMethod($transaction->payment_method, $transaction->currency);

        $validate = static::validateTransaction($transaction->currency, $transaction->payment_method, $transaction->amount, self::WAY_DEPOSIT);

        if ($validate !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $answer = [];

        try {
            //$transaction->commission = static::getCommission($transaction->currency, $transaction->amount);
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $currencyCode = self::_getCurrencyCode($transaction->currency);

        $requests['m_out']->transaction_id = $transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save();

        $params['success_url'] = $params['success_url'] . (strpos($params['success_url'], '?') ? '&' : '?') . "order_id={$transaction->id}";
        $params['fail_url'] = $params['fail_url'] . (strpos($params['fail_url'], '?') ? '&' : '?') . "order_id={$transaction->id}";

        $query = [
            'merchant_id' => $this->_merchant_id,
            'item_name' => 'Deposit',
            'order_id' => $transaction->id,
            'item_description' => $transaction->comment,
            'checkout_currency' => $currencyCode,
            'invoice_amount' => $transaction->amount,
            'invoice_currency' => $currencyCode,
            'success_url' => $params['success_url'],
            'failed_url' => $params['fail_url'],
            'language' => 'en',
            'payment_system' => $payment_method,
        ];

        $query['secret_hash'] = $this->_getSign($query);

        $transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $transaction->id;
        $requests['out']->data = $transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $result = $this->query('createinvoice', $query);

        $transaction->result_data = json_encode($result);

        $requests['pa_in']->transaction_id = $transaction->id;
        $requests['pa_in']->data = $transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-cryptonator-invoice');

        if (!isset($result['status'])) {
            if (!empty($result) && $this->_checkReceivedSign($result, 'callback')) {
                if (isset($result['invoice_id']) && $result['invoice_expires'] >= time()) {
                    $transaction->refund = $result['checkout_amount'];
                    $transaction->external_id = $result['invoice_id'];
                    $transaction->save(false);

                    $answer = [
                        'redirect' => [
                            'method' => 'GET',
                            'url' => static::INVOICE_URL . '/' . $result['invoice_id'],
                            'params' => [],
                        ],
                    ];
                    $answer['data'] = $transaction::getDepositAnswer($transaction);
                    $answer['data']['amount'] *= $coefficient;
                    $answer['data']['merchant_refund'] *= $coefficient;
                    $answer['data']['buyer_write_off'] *= $coefficient;

                } else {
                    $transaction->status = self::STATUS_ERROR;
                    $transaction->save();

                    $answer['status'] = 'error';

                    if (isset($result['message'])) {
                        $answer['message'] = json_encode($result['message']);
                    } else {
                        $answer['message'] = self::ERROR_OCCURRED;
                    }

                    $message = "Request url = '" . static::API_URL . "/createinvoice'\nRequest result = " . print_r($result, true);

                    Yii::error($message, 'payment-cryptonator');
                }
            } else {
                $answer = [
                    'status' => 'error',
                    'message' => 'Sign is not valid',
                ];

                $message = "Request url = '" . static::API_URL . "/createinvoice'\nRequest result = " . print_r($result, true) . "\ninfo=" . print_r($this->_query_info, true);

                Yii::error($message, 'payment-cryptonator-invoice');

                $transaction->status = self::STATUS_ERROR;
                $transaction->save();
            }
        } elseif ($this->_query_errno != CURLE_OK) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;
        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save();

            $message = "Request url = '" . static::API_URL . "/createinvoice'\nRequest result = " . print_r($result, true) . "\ninfo=" . print_r($this->_query_info, true);

            Yii::error($message, 'payment-cryptonator-invoice');

            $answer['status'] = 'error';

            if (isset($result['message'])) {
                $answer['message'] = json_encode($result['message']);
            } else {
                $answer['message'] = self::ERROR_OCCURRED;
            }
        }

        return $answer;
    }

    /**
     * Check if the seller has enough money.
     * Get required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $data
     * @return array
     */
    public function preWithDraw(array $data): array
    {
        return [
            'status' => 'error',
            'message' => self::ERROR_METHOD
        ];
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params): array
    {
        return [
            'status' => 'error',
            'message' => self::ERROR_METHOD
        ];
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

        if (in_array($transaction->status, self::getFinalStatuses())) {
            return true;
        }

        $currency_code = self::_getCurrencyCode($transaction->currency);

        if ($receiveData['merchant_id'] != $this->_merchant_id) {
            $this->logAndDie('Cryptonator receive() merchant_id is not equal' . " ({$transaction->id})",
                'Transaction merchant_id = ' . $this->_merchant_id . "\nreceived merchant_id = " . $receiveData['merchant_id'], 'Cryptonator receive() error',
                'cryptonator-receive');
        }

        if (!$this->_checkReceivedSign($receiveData, 'callback')) {
            return false;
        }

        if (isset($receiveData['status'])) {
            if ($receiveData['status'] == 'error') {
                // If status not success
                $transaction->status = self::STATUS_ERROR;
                $transaction->save(false);

                return true;
            }
        }
        if ($transaction->amount != $receiveData['invoice_amount']) {
            $this->logAndDie('Cryptonator receive() transaction amount not equal received amount' . " ({$transaction->id})",
                'Transaction amount = ' . $transaction->amount . "\nreceived amount = " . $receiveData['invoice_amount'], 'Cryptonator receive() error',
                'cryptonator-receive');
        }

        if (strtoupper($currency_code) != strtoupper($receiveData['invoice_currency'])) {
            $this->logAndDie('Cryptonator receive() different currency' . " ({$transaction->id})",
                'Transaction currency = ' . $currency_code . "\nreceived currency = " . $receiveData['invoice_currency'], 'Cryptonator receive() error',
                'cryptonator-receive');
        }

        $this->_setStatus($transaction, $receiveData['invoice_status']);

        $transaction->save(false);

        return true;
    }

    /**
     * Update not final statuses
     * @param object $transaction
     * @param null|object $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if (in_array($transaction->status, self::getNotFinalStatuses())) {
            $result_array = [
                'merchant_id' => $this->_merchant_id,
                'invoice_id' => $transaction->external_id
            ];

            $result_array['secret_hash'] = $this->_getSign($result_array, 'getstatus');

            $response = $this->query('getinvoice', $result_array);

            if (isset($model_req)) {
                $model_req->transaction_id = $transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            Yii::info($response, 'payment-cryptonator-update');

            if (isset($response['status'])) {
                $this->_setStatus($transaction, $response['status']);
                $transaction->save(false);

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $currency
     * @return string
     */
    private static function _getCurrencyCode(string $currency)
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency]['cryptonator']['currency_code'] ?? '';
    }

    /**
     * Transforming payment method to payment system method
     * @param string $paymentMethod
     * @param string $currency
     * @return null|string
     */
    public static function transformPaymentMethod(string $paymentMethod, string $currency)
    {
        $currencies = static::getSupportedCurrencies();

        return isset($currencies[$currency][$paymentMethod]['code']) ? $currencies[$currency][$paymentMethod]['code'] : null;
    }

    /**
     * Get query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['order_id']) ? ['id' => $data['order_id']] : [];
    }

    /**
     * Get PPS transaction id
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['order_id'] ?? 0;
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
     * @param $transaction
     * @param $status
     */
    private function _setStatus($transaction, $status)
    {
        switch ($status) {
            case 'unpaid':
            case 'confirming':
                $transaction->status = self::STATUS_PENDING;
                break;
            case 'cancelled':
                $transaction->status = self::STATUS_CANCEL;
                break;
            case 'mispaid':
                $transaction->status = self::STATUS_MISPAID;
                break;
            case 'paid':
                $transaction->status = self::STATUS_SUCCESS;
                break;
        }
    }

    /**
     * @param array $params
     * @param string $query
     * @return bool
     */
    private function _checkReceivedSign(array $params, string $query = 'createinvoice'): bool
    {
        $sign = $params['secret_hash'];
        unset($params['secret_hash']);

        $expectedSign = $this->_getSign($params, $query);

        if ($expectedSign != $sign) {
            Yii::error("Cryptonator receive() wrong sign or auth is received: expectedSign = $expectedSign \nSign = $sign", 'payment-cryptonator');

            return false;
        }

        return true;
    }

    /**
     * Checking final status
     * @param string $status
     * @return bool
     */
    private function isFinal(string $status): bool
    {
        $statuses = [
            'unpaid' => false,
            'confirming' => false,
            'paid' => true,
            'cancelled' => true,
            'mispaid' => false,
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking success status
     * @param string $status
     * @return bool
     */
    private function isSuccessStateCustom(string $status): bool
    {
        return $status == 'paid';
    }

    /**
     * Main query method
     * @param string $url
     * @param array $params
     * @return array|bool
     */
    private function query(string $url, array $params = [])
    {
        $query = http_build_query($params);

        $ch = curl_init(self::API_URL . '/' . trim($url, '/'));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Merchant.SDK/PHP');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'MIME-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);
        $this->_query_errno = curl_errno($ch);
        $this->_query_info = curl_getinfo($ch);

        $result = new \StdClass();
        $result->status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result->body = json_decode($response, true);

        curl_close($ch);

        if ($response !== false) {
            return $this->processResult($result);
        }

        return false;
    }

    /**
     * Get sign function
     * @param array $dataSet
     * @param string $query
     * @return string
     */
    private function _getSign(array $dataSet, string $query = 'createinvoice'): string
    {
        if (is_array($dataSet) && count($dataSet) > 0) {
            $string = '';

            switch ($query) {
                case 'createinvoice' :
                    $string =
                        ($dataSet['merchant_id']         ?? '') . '&' .
                        ($dataSet['item_name']           ?? '') . '&' .
                        ($dataSet['order_id']            ?? '') . '&' .
                        ($dataSet['item_description']    ?? '') . '&' .
                        ($dataSet['checkout_currency']   ?? '') . '&' .
                        ($dataSet['invoice_amount']      ?? '') . '&' .
                        ($dataSet['invoice_currency']    ?? '') . '&' .
                        ($dataSet['success_url']         ?? '') . '&' .
                        ($dataSet['failed_url']          ?? '') . '&' .
                        ($dataSet['confirmation_policy'] ?? '') . '&' .
                        ($dataSet['language']            ?? '');
                    break;
                case 'getinvoice' :
                case 'getstatus' :
                    $string =
                        ($dataSet['merchant_id'] ?? '') . '&' .
                        ($dataSet['invoice_id']  ?? '');
                    break;
                case 'listInvoices' :
                    $string =
                        ($dataSet['merchant_id']       ?? '') . '&' .
                        ($dataSet['invoice_status']    ?? '') . '&' .
                        ($dataSet['invoice_currency']  ?? '') . '&' .
                        ($dataSet['checkout_currency'] ?? '');
                    break;
                case 'callback' :
                    $data = $dataSet;
                    unset($data['secret_hash']);

                    $string = implode('&', $data);
                    break;
            }

            return sha1($string . '&' . $this->_secret_key);
        }

        return null;
    }

    private function processResult($result)
    {
        if ($result->status_code == 400) {
            return [
                'status' => 'error',
                'message' => "Server error: " . $result->body['error'] . '. Status code = ' . $result->status_code,
            ];
        }

        return $result->body;
    }

    /**
     * Validation transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    public static function validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        $method = $currencies[$currency][$method];

        if (!isset($method[$way]) || !$method[$way]) {
            return "Payment system does not support '{$way}'";
        }

        $method['max'] = $method['min'] * 100000;

        if ($method['min'] >= $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
        if ($method['max'] <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;

        if ($method['min'] >= $amount || $method['max'] <= $amount) {
            return "Amount should to be more than '{$method['min']}' and less than '{$method['max']}'";
        }

        return true;
    }

    /**
     * Get sum of commission Cryptonators
     * @param string $currency
     * @param float $amount
     * @return float
     */
    private static function getCommission(string $currency, float $amount): float
    {
        $returns = 0;

        $currencies = static::getSupportedCurrencies();
        if (!empty($currencies[$currency]['cryptonator']['commission'])) {
            $currency_code = $currencies[$currency]['cryptonator']['currency_code'];

            $commission_data = $currencies[$currency]['cryptonator']['commission'];
            $commission_value = $commission_data['name'][$currency_code];
            $commission_measurement = $commission_data['measurement'][$currency_code];

            if ($commission_measurement == $currency_code) {
                $returns = $commission_value;
            } elseif ($commission_measurement == 'percent') {
                $returns = $amount * $commission_value / (100 - $commission_value);
            }

            return round(floatval($returns), 8);
        }

        return 'unknown';
    }

    /**
     * Get fields for filling
     * @param string $currency
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency]['cryptonator']['fields'][$way] ?? [];
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
     * Get supported currencies and payment methods
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return require(__DIR__ . '/currency_lib.php');
    }
}