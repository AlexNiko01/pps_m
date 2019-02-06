<?php

namespace pps\cubits;

use api\classes\ApiError;
use common\models\Transaction;
use Yii;
use yii\base\Event;
use yii\db\{
    ActiveQuery, Exception, ActiveRecord
};
use yii\web\Response;
use pps\payment\Payment;
use pps\payment\ICryptoCurrency;
use pps\querybuilder\QueryBuilder;
use yii\base\InvalidParamException;

/**
 * Class Cubits
 * @package pps\cubits
 */
class Cubits extends Payment implements ICryptoCurrency
{
    const API_URL = 'https://api.cubits.com';

    protected static $methods = ['testQuery'];

    /**
     * @var string
     */
    private $_secret_key;
    /**
     * @var string
     */
    private $_cubits_key;
    /**
     * @var Event
     */
    private $_event;
    /**
     * @var Transaction
     */
    private $_transaction;
    /**
     * @var QueryBuilder
     */
    private $_request;
    /**
     * @var array
     */
    private static $_allow_ip = [
        '104.20.242.13',
        '5.9.49.227', // pre-production
    ];


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['secret_key'])) {
            throw new InvalidParamException('"secret_key" empty');
        }
        if (empty($data['cubits_key'])) {
            throw new InvalidParamException('"cubits_key" empty');
        }

        $this->_secret_key = $data['secret_key'];
        $this->_cubits_key = $data['cubits_key'];

        $this->_event = $data['event'] ?? null;
    }

    /**
     * @param $data
     * @return string
     */
    public function testQueryMethod($data)
    {
        $resp = $this->_query('test', [
            'description' => 'query test',
            'param' => 'value'
        ], true);
        return json_encode([$resp->getResponse(true), $resp->getHttpCode()]);
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        return [
            'data' => [
                'fields' => [],
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'buyer_write_off' => null,
                'merchant_refund' => $params['amount'],
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
        $this->_transaction = $params['transaction'];
        $requests = $params['requests'];

        $coefficient = self::getCoefficient($this->_transaction->currency);
        $this->_transaction->amount = $this->_transaction->amount / $coefficient;

        $answer = [];

        try {
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $this->logger->log($this->_transaction->id, 1, $requests['merchant']);

        $query = [
            'currency' => $this->_transaction->currency,
            'price' => number_format($this->_transaction->amount, 8, '.', ''),
            //'share_to_keep_in_btc' => 0,
            'description' => $this->_transaction->comment,
            'callback_url' => $params['callback_url'],
            'success_url' => $params['success_url'],
            'cancel_url' => $params['fail_url'],
            'reference' => $this->_transaction->id
        ];

        $this->_transaction->query_data = json_encode($query);

        $this->logger->log($this->_transaction->id, 2, $this->_transaction->query_data);

        $request = $this->_query('invoices', $query, true);

        $result = $request->getResponse(true);

        $this->_transaction->result_data = json_encode($result);

        $this->logger->log($this->_transaction->id, 3, $this->_transaction->result_data);

        Yii::info(['query' => $query, 'result' => $result], 'payment-cubits-invoice');

        if (isset($result['invoice_url'])) {

            $this->_transaction->external_id = $result['id'];
            $this->_transaction->status = self::STATUS_CREATED;
            $this->_transaction->amount = $result['invoice_amount'];
            $this->_transaction->refund = $result['merchant_amount'];
            $this->_transaction->save(false);

            $answer['redirect'] = [
                'method' => 'get',
                'url' => $result['invoice_url'],
                'params' => [],
            ];

            $answer['data'] = $this->_transaction::getDepositAnswer($this->_transaction);

        } else if ($request->getErrno() != CURLE_OK) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;

        } else {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = $result['message'] ?? self::ERROR_OCCURRED;

            $this->logger->log($this->_transaction->id, 100, $answer);
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
        $this->_transaction = $data['transaction'];
        $receiveData = $data['receive_data'];

        if (in_array($this->_transaction->status, self::getFinalStatuses())) {
            return true;
        }

        if (!self::_checkIP()) {
            $address = $_SERVER['REMOTE_ADDR'] ?? 'undefined';
            Yii::error("Cubits receive() from undefined server,\nIP = {$address}", 'payment-cubits-receive');
            return false;
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        if (in_array('tx_ref_code', array_keys($receiveData))) {
            return $this->_receiveChannel($receiveData);
        } else {
            return $this->_receiveInvoice($receiveData);
        }
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
        $coefficient = self::getCoefficient($params['currency']);
        $amount = $params['amount'] / $coefficient;

        $validate = self::_validateTransaction($params['currency'], $params['payment_method'], $amount, self::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        return [
            'data' => [
                'fields' => self::_getFields($params['currency'], $params['payment_method'], self::WAY_WITHDRAW),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => $params['amount'],
                'buyer_receive' => $params['amount'],
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
        $this->_transaction = $params['transaction'];
        $requests = $params['requests'];

        $coefficient = self::getCoefficient($this->_transaction->currency);
        $this->_transaction->amount = $this->_transaction->amount / $coefficient;

        try {
            $this->_transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $validate = self::_validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, self::WAY_WITHDRAW);

        if ($validate !== true) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);
            $this->logger->log($this->_transaction->id, 100, $validate);

            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $this->logger->log($this->_transaction->id, 1, $requests['merchant']);

        $amount = $this->_transaction->amount;

        if (!$purseAmount = $this->_getBalance($this->_transaction->currency)) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);
            $this->logger->log($this->_transaction->id, 100, 'Error getting wallet balance!');
            return [
                'status' => 'error',
                'message' => 'Error getting wallet balance!'
            ];
        }

        if ($purseAmount <= $amount) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);
            $this->logger->log($this->_transaction->id, 100, "Insufficient funds!");
            
            return [
                'status' => 'error',
                'message' => "Insufficient funds!",
                'code' => ApiError::LOW_BALANCE
            ];
        }

        if ($this->_transaction->commission_payer === self::COMMISSION_MERCHANT) {
            $amountWithFee = $amount;
        } else {
            $amountWithFee = $amount;
        }

        $requisites = json_decode($this->_transaction->requisites, true);

        if (!isset($requisites['address'])) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);
            $this->logger->log($this->_transaction->id, 100, "Required param 'address' not found");
            
            return [
                'status' => 'error',
                'message' => "Required param 'address' not found",
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $query = [
            'amount' => number_format($amountWithFee, 8, '.', ''),
            'reference' => $this->_transaction->id
        ];

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites);
        }

        $this->_transaction->query_data = json_encode($query);

        $request = $this->_query('send_money', $query, true);
        $result = $request->getResponse(true);

        $this->_transaction->query_data = json_encode($query);

        $this->logger->log($this->_transaction->id, 2, $this->_transaction->query_data);

        $this->_transaction->result_data = json_encode($result);

        $this->logger->log($this->_transaction->id, 3, $this->_transaction->result_data);

        Yii::info(['query' => $query, 'result' => $result, 'headers' => $this->_request->getHeaders()], 'payment-cubits-withdraw');

        if (isset($result['tx_ref_code'])) {

            $this->_transaction->external_id = $result['tx_ref_code'];
            $this->_transaction->receive = $amountWithFee;
            $this->_transaction->write_off = $amountWithFee;
            $this->_transaction->status = self::STATUS_SUCCESS;
            $this->_transaction->save(false);

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

            $this->_transaction->save(false);

        } else if ($request->getErrno() == CURLE_OPERATION_TIMEOUTED) {
            $this->_transaction->status = self::STATUS_TIMEOUT;
            $this->_transaction->save(false);
            //$params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } else if ($request->getErrno() != CURLE_OK) {
            $this->_transaction->status = self::STATUS_NETWORK_ERROR;
            $this->_transaction->result_data = $request->getErrno();
            $this->_transaction->save(false);
            //$params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } else {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);

            $answer = ['status' => 'error'];

            $answer['message'] = $result['message'] ?? self::ERROR_OCCURRED;

            $this->logger->log($this->_transaction->id, 100, $answer);
        }

        return $answer;
    }

    /**
     * Too slow method
     * @param object $transaction
     * @param ActiveRecord $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if ($transaction->status === self::WAY_WITHDRAW) {
            return false;
        }

        if (in_array($transaction->status, self::getNotFinalStatuses())) {
            $response = $this->_query('invoices/' . $transaction->external_id, [])->getResponse(true);

            $this->logger->log($transaction->id, 8, $response);

            Yii::info($response, 'payment-cubits');

            if (isset($response['status'])) {
                $this->_setStatus($response['status']);
                $transaction->save(false);

                return true;
            }
        }

        return false;
    }

    /**
     * @param int|string $buyer_id
     * @param string $callback_url
     * @param $brand_id
     * @return array
     */
    public function getAddress($buyer_id, $callback_url, $brand_id, $currency = 'BTC'): array
    {
        $query = [
            'receiver_currency' => $currency,
            'name' => "{$brand_id}-{$buyer_id}",
            'description' => "User #{$buyer_id}",
            //'callback_url' => $callback_url,
            'txs_callback_url' => $callback_url,
            //'share_to_keep_in_btc' => 0,
            'reference' => json_encode([
                'buyer_id' => $buyer_id,
                'brand_id' => $brand_id,
            ])
        ];

        $result = $this->_query('channels', $query, true)->getResponse(true);

        Yii::info(['query' => $query, 'result' => $result], 'payment-cubits-get_address');

        if (isset($result['address'])) {
            return [
                'data' => [
                    'address' => $result['address'],
                    'field' => $result['id']
                ],
            ];
        } else {
            $answer['status'] = 'error';

            Yii::warning(['query' => $query, 'result' => $result], 'payment-cubits-get_address');

            if (isset($result)) {
                $answer['message'] = json_encode($result);
            } else {
                $answer['message'] = self::ERROR_OCCURRED;
            }

            return $answer;
        }
    }

    /**
     * Receive callback if his is from invoice
     * @param array $receiveData
     * @return bool
     */
    private function _receiveInvoice(array $receiveData): bool
    {
        if (!self::checkRequiredParams(['id', 'status', 'address', 'invoice_currency', 'invoice_amount'], $receiveData)) {
            return false;
        }

        $amount = sprintf('%0.8f', $this->_transaction->amount);

        if ($amount !== $receiveData['merchant_amount']) {
            Yii::error("Transaction amount = {$amount}\nreceived amount = {$receiveData['merchant_amount']}", 'payment-cubits-receive');
            return false;
        }

        if ($this->_transaction->currency !== $receiveData['merchant_currency']) {
            Yii::error("Transaction currency = {$this->_transaction->currency}\nreceived currency = {$receiveData['merchant_currency']}", 'payment-cubits-receive');
            return false;
        }

        if ($this->_transaction->way === self::WAY_DEPOSIT) {
            $this->_setStatus($receiveData['status']);
        }

        $this->_transaction->save(false);

        return false;
    }

    /**
     * Receive callback if his is from channel
     * @param array $receiveData
     * @return bool
     */
    private function _receiveChannel(array $receiveData): bool
    {
        if (!self::checkRequiredParams(['state', 'channel_id', 'sender', 'receiver'], $receiveData)) {
            //$this->_transaction->delete();
            $this->logger->log($this->_transaction->id, 100, 'Some required parameters was not found!');
            return false;
        }

        if ($this->_checkDoubleSpend($receiveData['sender']['bitcoin_txid'])) {
            $this->_transaction->status = self::STATUS_DSPEND;
            $this->_transaction->save(false);
            $this->logger->log($this->_transaction->id, 100, 'Double spend was recognised');
            return true;
        }

        //if (isset($receiveData['receiver']['amount'])) $this->_transaction->refund = $receiveData['receiver']['amount'];

        if ($this->_transaction->way === self::WAY_DEPOSIT) {
            $this->_setStatus($receiveData['state']);
        }

        $this->_transaction->save(false);

        return false;
    }

    /**
     * Get information about existing channel.
     * @param string $channel_id
     * @return mixed
     */
    private function _getChannelInfo(string $channel_id)
    {
        return $this->_query(["channels", $channel_id], []);
    }

    /**
     * Get information about all transactions of an existing channel.
     * @param string $channel_id
     * @return mixed
     */
    private function _getChannelTxs(string $channel_id)
    {
        return $this->_query(["channels", $channel_id, 'txs'], []);
    }

    /**
     * Get information about an individual transactions of a channel.
     * @param string $channel_id
     * @param string $tx
     * @return mixed
     */
    private function _getChannelTx(string $channel_id, string $tx)
    {
        return $this->_query(["channels", $channel_id, 'txs', $tx], []);
    }

    /**
     * Main method for queries
     * @param array|string $path
     * @param $data
     * @param bool $post
     * @return \pps\querybuilder\src\IQuery
     */
    private function _query($path, array $data = [], bool $post = false)
    {
        $this->_request = new QueryBuilder();

        if (is_array($path)) {
            $url = "/api/v1";

            foreach ($path as $item) {
                $url .= "/{$item}";
            }

        } else {
            $url = "/api/v1/{$path}";
        }

        $this->_request->setUrl(self::API_URL . $url);

        $this->_request->setParams($data);

        $this->_request->setHeaders([
            'Content-Type' => 'application/vnd.api+json',
            'Accept' => 'application/json',
            'User-Agent' => 'Cubits/PHP v0.0.1',
            'X-Cubits-Key' => $this->_cubits_key
        ]);

        $this->_setSecureHeaders($url, $data, $post);

        $this->_request->setOptions([
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
            CURLOPT_TIMEOUT => self::$timeout,
        ]);

        if ($post) {
            $this->_request->json(true, false);
            $this->_request->asPost();
        } else {
            $this->_request->setOption(CURLOPT_HTTPGET, true);
        }

        return $this->_request->send();
    }

    /**
     * @param string $tr_hash
     * @return bool
     */
    private function _checkDoubleSpend(string $tr_hash): bool
    {
        $url = "https://blockchain.info/tx/{$tr_hash}";

        $request = (new QueryBuilder($url))
            ->setParams(['format' => 'json'])
            ->send();

        $result = $request->getResponse(true);

        return (isset($result['double_spend']) && $result['double_spend'] == true) || (isset($result['rbf']) && $result['rbf'] == true);
    }

    /**
     * Get random int string no more then 64 bit
     * @return integer
     */
    private function _getNonce()
    {
        $str = sprintf('%0.0f', round(microtime(true) * 1000000));

        for ($i = 0; $i < 5; $i++)
            $str .= rand(0, 9);

        return $str;
    }

    /**
     * Set security headers
     * @param string $url_path
     * @param array $request_data
     * @param bool $post
     */
    private function _setSecureHeaders(string $url_path, array $request_data, bool $post)
    {
        $nonce_s = $this->_getNonce();

        $data = '';

        if ($post) {
            $data = json_encode($request_data);
        }

        $msg = utf8_encode($url_path) . $nonce_s . hash('sha256', utf8_encode($data), false);

        $signature = hash_hmac('sha512', $msg, $this->_secret_key);

        $this->_request->setHeader('X-Cubits-Nonce', $nonce_s);
        $this->_request->setHeader('X-Cubits-Signature', $signature);
    }

    /**
     * Get account balance
     * @param string $currency
     * @return int|float
     */
    private function _getBalance(string $currency)
    {
        $response = $this->_query('accounts', [])->getResponse(true);

        Yii::warning(['message' => "Get balance", 'data' => $response, 'merchant' => $this->_transaction->brand_id], 'payment-cubits-get_balance');

        if (!isset($response['accounts'])) {
            return false;
        }

        foreach ($response['accounts'] as $item) {
            if ($item['currency'] === $currency) {
                return $item['balance'];
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private static function _checkIP(): bool
    {
        if (defined('YII_ENV') && YII_ENV === 'dev') {
            self::$_allow_ip[] = '127.0.0.1';
        }

        return in_array($_SERVER['REMOTE_ADDR'] ?? '', self::$_allow_ip);
    }

    /**
     * @param $paymentSystemId
     * @param $userAddress ActiveQuery
     * @param $receive_data
     * @return array|bool|string
     */
    public static function fillTransaction($paymentSystemId, $userAddress, $receive_data)
    {
        if (self::checkRequiredParams(['id', 'status', 'address', 'invoice_currency', 'invoice_amount'], $receive_data)) {
            return 'invoice';
        }

        if (!self::checkRequiredParams(['tx_ref_code', 'state', 'channel_id', 'sender', 'receiver'], $receive_data)) {
            Yii::warning(['message' => "Required parameters not found", 'data' => $receive_data], 'payment-cubits-receive');
            return false;
        }

        if (!self::_checkIP()) {
            return false;
        }

        $user = $userAddress->where([
            'field' => $receive_data['channel_id'],
            'payment_system_id' => $paymentSystemId
        ])
            ->asArray()
            ->one();

        if (empty($user)) {
            return false;
        }

        $urls = [];

        if (!empty($user['callback_url'])) {
            $urls['callback_url'] = $user['callback_url'];
        }

        return [
            'brand_id' => $user['brand_id'],
            'payment_system_id' => $paymentSystemId,
            'way' => self::WAY_DEPOSIT,
            'currency' => 'BTC',
            'amount' => $receive_data['receiver']['amount'],
            'refund' => $receive_data['receiver']['amount'],
            'payment_method' => 'bitcoin',
            'comment' => "Deposit from user #{$user['user_id']}",
            'merchant_transaction_id' => $receive_data['tx_ref_code'],
            'external_id' => $receive_data['tx_ref_code'],
            'commission_payer' => self::COMMISSION_BUYER,
            'buyer_id' => $user['user_id'],
            'status' => self::transformStatus($receive_data['state']),
            'urls' => json_encode($urls)
        ];
    }

    /**
     * Getting supported currencies
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return [
            'BTC' => [
                'bitcoin' => [
                    'name' => 'Bitcoin',
                    'fields' => [
                        'withdraw' => [
                            'address' => [
                                'label' => 'Address',
                                'regex' => '^[0-9a-zA-Z]{32,34}$',
                                'example' => '19jJyiC6DnKyKvPg38eBE8R6yCSXLLEjqw',
                                'type' => 'text'
                            ]
                        ],
                        'deposit' => []
                    ],
                    'deposit' => true,
                    'withdraw' => true,
                    'min' => 0.00001,
                    'max' => 0.1
                ]
            ]
        ];
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
     * Getting query for search transaction after success or fail transaction
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['tx_ref_code']) ? ['external_id' => $data['tx_ref_code']] : [];
    }

    /**
     * Getting transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['reference'] ?? 0;
    }

    /**
     * Getting success answer for stopping send callback data
     * @return mixed
     */
    public static function getSuccessAnswer()
    {
        return '*ok*';
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
     * @param string $currency
     * @param string $method
     * @param string $type
     * @return array
     */
    private static function _getFields(string $currency, string $method, string $type): array
    {
        $currencies = self::getSupportedCurrencies();

        return $currencies[$currency][$method]['fields'][$type] ?? [];
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

        if (isset($method['min']) && isset($method['max'])) {
            if ($method['min'] > $amount || $method['max'] <= $amount) {
                if ($method['min'] > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
                if ($method['max'] <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;
                return "Amount should to be more than '{$method['min']}' and less than '{$method['max']}'";
            }
        }

        if (isset($method['min'])) {
            if ($method['min'] > $amount) {
                self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
                return "Amount should to be more than '{$method['min']}'";
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        return [
            'bitcoin' => [
                'withdraw' => [
                    'address' => [
                        'label' => 'Bitcoin address',
                        'regex' => '^\\w{32,34}$',
                        'example' => '18uf5WsXDrNptE7vUh5CLgFCUTgZRirDqa',
                        'required' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * @param $status
     */
    private function _setStatus($status)
    {
        switch ($status) {
            case 'pending':
                $this->_transaction->status = self::STATUS_PENDING;
                break;
            case 'completed':
                $this->_transaction->status = self::STATUS_SUCCESS;
                break;
            case 'overpaid':
            case 'underpaid':
                $this->_transaction->status = self::STATUS_MISPAID;
                break;
            case 'aborted':
            case 'timeout':
                $this->_transaction->status = self::STATUS_CANCEL;
                break;
        }
    }

    /**
     * @param string $status
     * @return bool
     */
    private static function _isFinalDepositStatus(string $status): bool
    {
        $statuses = [
            'pending' => false, // Initial state, invoice has been created but no payment was received yet
            'completed' => true, // Success state, invoice has been fully paid
            'overpaid' => true, // Success state, invoice has been fully paid but additional funds were received
            'underpaid' => false, // Intermediary state, insufficient payment was received
            'aborted' => true, // Failure state, payment was cancelled by the customer
            'timeout' => true, // Failure state, no sufficient payment was received before the invoice expired
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * @param $status
     * @return int|mixed
     */
    private static function transformStatus($status) {
        $statuses = [
            'pending' => self::STATUS_PENDING,
            'completed' => self::STATUS_SUCCESS,
            'overpaid' => self::STATUS_SUCCESS,
            'underpaid' => self::STATUS_MISPAID,
            'aborted' => self::STATUS_CANCEL,
            'timeout' => self::STATUS_CANCEL,
        ];

        return $statuses[$status];
    }

    /**
     * @param string $status
     * @return bool
     */
    private static function _isSuccessStatus(string $status): bool
    {
        return in_array($status, ['completed', 'overpaid']);
    }

    /**
     * @param array $params
     * @return bool
     */
    private function _checkReceivedSign(array $params): bool
    {
        $headers = Yii::$app->request->headers;

        $callbackId = $headers->get('X-Cubits-Callback-Id');
        $key = $headers->get('X-Cubits-Key');
        $signature = $headers->get('X-Cubits-Signature');

        $msg = $callbackId . hash('sha256', json_encode($params, JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_HEX_AMP), false);
        $expectedSignature = hash_hmac('sha512', utf8_encode($msg), $this->_secret_key);

        if ($key != $this->_cubits_key || $signature != $expectedSignature) {
            Yii::error("Cubits receive() wrong sign or auth is received: \nexpectedSignature: $expectedSignature \nsignature: $signature, \nexpectedKey: $this->_cubits_key\nKey: $key", 'payment-cubits');

        } else {
            return true;
        }

        return false;
    }
}
