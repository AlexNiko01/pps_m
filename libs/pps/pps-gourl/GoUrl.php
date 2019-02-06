<?php

namespace pps\gourl;

use api\classes\ApiError;
use Yii;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * Class GoUrl
 * @package pps\gourl
 */
class GoUrl extends Payment
{
    const API_URL = 'https://coins.gourl.io';
    const RESULT_URL = 'https://coins.gourl.io/result.php';
    const CRYPTOBOX_VERSION = "1.8";

    /**
     * @var object
     */
    private $_transaction;
    /**
     * @var string
     */
    private $_public_key;
    /**
     * @var string
     */
    private $_private_key;
    /**
     * @var string
     */
    private $_webdev_key;
    /**
     * @var string
     */
    private $_box_id;
    /**
     * @var array
     */
    private $_allow_ip;
    /**
     * @var string
     */
    private $_language = 'en';
    /**
     * @var string
     */
    private $_period = '1 HOUR';
    /**
     * @var int
     */
    private $_errno;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (!$data['public_key']) {
            throw new InvalidParamException('public_key empty');
        }
        if (!$data['private_key']) {
            throw new InvalidParamException('private_key empty');
        }
        if (!$data['box_id']) {
            throw new InvalidParamException('box_id empty');
        }
        if (isset($data['webdev_key'])) {
            $this->_webdev_key = $data['webdev_key'];
        }

        $this->_public_key = $data['public_key'];
        $this->_private_key = $data['private_key'];
        $this->_box_id = $data['box_id'];

        $this->_allow_ip = [
            '51.255.140.174',
            '51.254.199.21'
        ];

        if (YII_ENV && YII_ENV == 'dev') {
            $this->_allow_ip[] = '127.0.0.1';
            $this->_allow_ip[] = '5.9.49.227';
        }
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], 'deposit');

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        return [
            'data' => [
                'fields' => [],
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
        $this->_transaction = $params['transaction'];
        $requests = $params['requests'];

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => Payment::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = static::validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, 'deposit');

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

        $requests['m_out']->transaction_id = $this->_transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        $query = [
            'amount' => (float)$this->_transaction->amount,
            'amountUSD' => 0,
            'period' => $this->_period,
            'language' => $this->_language,
            'orderID' => $this->_transaction->id,
            'userID' => $this->_transaction->buyer_id,
            'userFormat' => 'MANUAL'
        ];

        $c = substr(static::_right(static::_left($this->_public_key, "PUB"), "AA"), 5);
        $query['coinName'] = static::_left($c, "77");

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $result = $this->_query($query);

        Yii::info(['query' => $query, 'result' => $result], 'payment-gourl-invoice');

        $this->_transaction->result_data = json_encode($result);

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        if ($result && empty($result['err'])) {

            $this->_transaction->external_id = $result['order'];
            $this->_transaction->status = self::STATUS_CREATED;
            $this->_transaction->refund = $query['amount'];
            $this->_transaction->write_off = $result['amount'];

            $this->_transaction->save(false);

            $frontend_link = rtrim(Yii::$app->params['frontend_link'], '/');

            $answer['redirect'] = [
                'method' => 'URL',
                'url' => $frontend_link . '/gourl/' . $result['order'],
                'params' => []
            ];

            $answer['data'] = $this->_transaction::getDepositAnswer($this->_transaction);

        } else if ($this->_errno != CURLE_OK) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;

        } else {
            $answer['status'] = 'error';

            if (isset($result['err'])) {
                $answer['message'] = $result['err'];
            } else {
                $answer['message'] = Payment::ERROR_OCCURRED;
            }

            $message = "Request url = " . static::API_URL;
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-gourl-invoice');
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

        if (in_array($this->_transaction->status, Payment::getFinalStatuses())) {
            return true;
        }

        $need = ['amount', 'coinlabel', 'status'];

        if (!self::checkRequiredParams($need, $receiveData)) {
            Yii::error([
                'receive_data' => $receiveData,
                'need' => $need
            ], 'payment-gourl-receive');
            return false;
        }

        if (!$this->_checkIP()) {
            $this->logAndDie(
                'GoUrl receive() from undefined server',
                'IP = ' . Yii::$app->request->getUserIP(),
                "Undefined server\n"
            );
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        if ($this->_transaction->amount != $receiveData['amount']) {
            $this->logAndDie(
                'GoUrl receive() transaction amount not equal received amount',
                "Transaction amount = {$this->_transaction->amount}\nreceived amount = {$receiveData['amount']}",
                "Transaction amount not equal received amount\n"
            );
        }

        if (strtolower($data['currency']) != strtolower($receiveData['coinlabel'])) {
            $this->logAndDie(
                'GoUrl receive() different currency',
                "GoUrl currency = {$data['currency']}\nreceived currency = {$receiveData['coinlabel']}",
                "Different currency\n"
            );
        }

        if ($receiveData['status'] === 'payment_received') {
            $this->_transaction->status = Payment::STATUS_SUCCESS;
            $this->_transaction->save(false);

            return true;
        }

        if ($receiveData['status'] === 'payment_received_unrecognised') {
            $this->_transaction->status = Payment::STATUS_MISPAID;
            $this->_transaction->save(false);

            return true;
        }

        return false;
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
    public function withDraw(array $params)
    {
        return [
            'status' => 'error',
            'message' => self::ERROR_METHOD
        ];
    }

    /**
     * @param object $transaction
     * @param null $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if (in_array($transaction->status, Payment::getNotFinalStatuses())) {
            if ($transaction->way === 'deposit') {
                $response = $this->_queryGetStatus($transaction->id, $transaction->buyer_id);

                if (isset($response['status']) && static::_isFinalDeposit($response['status'])) {

                    if (static::_isSuccessDeposit($response['status'])) {
                        $transaction->status = self::STATUS_SUCCESS;
                    } else {
                        $transaction->status = self::STATUS_ERROR;
                    }

                } else {
                    $transaction->status = self::STATUS_PENDING;
                }

                $transaction->save(false);

                if ($model_req) {
                    $model_req->transaction_id = $transaction->id;
                    $model_req->data = json_encode($response);
                    $model_req->type = 8;
                    $model_req->save(false);
                }

                return true;
            }

            if ($transaction->way === 'withdraw') {
                return false;
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function _checkReceivedSign(array $data): bool
    {
        $hash = $data['private_key_hash'];
        unset($data['private_key_hash']);
        $expectedHash = hash("sha512", $this->_private_key);

        if ($hash != $expectedHash) {
            Yii::error("GoUrl receive() wrong private_key_hash is received: expectedHash = $expectedHash \nHash = $hash", 'payment-gourl');
        } else {
            return true;
        }

        return false;
    }

    /**
     * Main query method
     * @param array $params
     * @return boolean|object
     */
    private function _query(array $params)
    {
        $url = $this->_createUrl($params);

        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
            CURLOPT_TIMEOUT => self::$timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Get_Json_Values PHP Class ' . static::CRYPTOBOX_VERSION
        ];

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        //$info = curl_getinfo($ch);
        //$error = curl_error($ch);
        $this->_errno = curl_errno($ch);

        curl_close($ch);

        if ($response === false) {

            return false;

        } else {

            $responseJson = json_decode($response, true);

            if (isset($responseJson['data_hash'])) {
                $hash = $responseJson['data_hash'];
                unset($responseJson['data_hash']);

                $expectedHash = strtolower(hash("sha512", $this->_private_key . json_encode($responseJson) . $this->_private_key));

                if ($hash == $expectedHash) {
                    return $responseJson;
                }
            }

            return false;
        }
    }

    /**
     * @param $orderID
     * @param $userID
     * @return bool|mixed
     */
    private function _queryGetStatus($orderID, $userID)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $hash = md5($this->_box_id . $this->_private_key . $userID . $orderID . $this->_language . $this->_period . $ip);

        $data = array(
            "r" => $this->_private_key,
            "b" => $this->_box_id,
            "o" => $orderID,
            "u" => $userID,
            "l" => $this->_language,
            "e" => $this->_period,
            "i" => $ip,
            "h" => $hash
        );

        $ch = curl_init(static::RESULT_URL);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
            CURLOPT_TIMEOUT => self::$timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
        ];

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        if (!$result) {
            return false;
        }

        return json_decode($result, true);
    }

    /**
     * Sign function
     * @param array $data
     * @return string
     */
    private function _getSign(array $data): string
    {
        $hashArray = [
            $this->_box_id,
            $data['coinName'],
            $this->_public_key,
            $this->_private_key,
            $this->_webdev_key,
            $data['amount'],
            $data['amountUSD'],
            $data['period'],
            $data['language'],
            $data['orderID'],
            $data['userID'],
            $data['userFormat'],
            Yii::$app->request->getUserIP()
        ];

        $hash = md5(implode('|', $hashArray));

        return $hash;
    }

    /**
     * @param array $params
     * @return string
     */
    private function _createUrl(array $params): string
    {
        $data = [
            "b" => $this->_box_id,
            "c" => $params['coinName'],
            "p" => $this->_public_key,
            "a" => $params['amount'],
            "au" => $params['amountUSD'],
            "pe" => $params['period'],
            "l" => $params['language'],
            "o" => $params['orderID'],
            "u" => $params['userID'],
            "us" => $params['userFormat'],
            "j" => 1,
            "d" => base64_encode(Yii::$app->request->getUserIP()),
        ];

        $data['h'] = $this->_getSign($params);

        if ($this->_webdev_key) {
            $data["w"] = $this->_webdev_key;
        }

        $data["z"] = rand(0, 10000000);

        $url = static::API_URL;

        foreach ($data as $k => $v) {
            $url .= "/" . $k . "/" . rawurlencode($v);
        }

        return $url;
    }

    /**
     * @return bool
     */
    private function _checkIP(): bool
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return in_array($_SERVER['HTTP_X_REAL_IP'], $this->_allow_ip);
        }

        return in_array($_SERVER['REMOTE_ADDR'], $this->_allow_ip);
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

        if (!isset($currencies[$currency][$method])) {
            return "Method '{$method}' does not exist";
        }

        $method = $currencies[$currency][$method];

        if (!isset($method[$way]) || !$method[$way]) {
            return "Payment system does not support '{$way}'";
        }

        if ((isset($method['min']) && $method['min'] > $amount) || (isset($method['max']) && $method['max'] <= $amount)) {
            if (isset($method['min']) && $method['min'] > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            if (isset($method['max']) && $method['max'] <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;
            return "Amount should to be more than '{$method['min']}' and less than '{$method['max']}'";
        }

        return true;
    }

    /**
     * Getting query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['order']) ? ['id' => $data['order']] : [];
    }

    /**
     * Getting transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['order'] ?? 0;
    }

    /**
     * Getting success answer for stopping send callback data
     * @param null $oldStatus
     * @param null $newStatus
     * @return string
     */
    public static function getSuccessAnswer($oldStatus = null, $newStatus = null)
    {
        return $oldStatus === $newStatus ? 'cryptobox_nochanges' : 'cryptobox_updated';
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
     * Getting supported currencies
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return require(__DIR__ . '/currency_lib.php');
    }

    /**
     * Checking final status for deposit
     * @param string $status
     * @return boolean
     */
    private static function _isFinalDeposit(string $status): bool
    {
        $statuses = [
            'payment_received' => true,
            'payment_not_received' => false,
            'payment_received_unrecognised' => true,
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking success status for deposit
     * @param string $status
     * @return bool
     */
    private static function _isSuccessDeposit(string $status): bool
    {
        return $status === 'payment_received';
    }

    /**
     * @param string $str
     * @param string $findme
     * @param bool $firstpos
     * @return bool|string
     */
    private static function _left(string $str, string $findme, bool $firstpos = true)
    {
        $pos = ($firstpos) ? stripos($str, $findme) : strripos($str, $findme);

        if ($pos === false) {
            return $str;
        }

        return substr($str, 0, $pos);
    }

    /**
     * @param string $str
     * @param string $findme
     * @param bool $firstpos
     * @return bool|string
     */
    private static function _right(string $str, string $findme, bool $firstpos = true)
    {
        $pos = ($firstpos) ? stripos($str, $findme) : strripos($str, $findme);

        if ($pos === false) {
            return $str;
        }

        return substr($str, $pos + strlen($findme));
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        return [];
    }
}