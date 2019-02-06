<?php

namespace pps\freekassa;

use api\classes\ApiError;
use backend\components\Notification;
use backend\models\Dispatch;
use common\models\Transaction;
use pps\payment\ICryptoCurrency;
use pps\querybuilder\QueryBuilder;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * Class FreeKassa
 * @package pps\freekassa
 */
class FreeKassa extends Payment implements ICryptoCurrency
{
    const API_URL = 'https://www.free-kassa.ru/api.php';
    const API_INVOICE_URL = 'https://www.free-kassa.ru/merchant/cash.php';
    const API_WALLET_URL = 'https://wallet.free-kassa.ru/api_v1.php';
    const BLOCKCHAIN_URL = 'https://blockchain.info/';

    /** @var string */
    private $_wallet;
    /** @var string */
    private $_api_key;
    /** @var string */
    private $_merchant_id;
    /** @var string */
    private $_secret_word1;
    /** @var string */
    private $_secret_word2;
    /** @var array */
    private static $_allow_ip = [
        '136.243.38.147',
        '136.243.38.149',
        '136.243.38.150',
        '136.243.38.151',
        '136.243.38.189',
        '88.198.88.98'
    ];
    /** @var int */
    private $_NOC = 6;
    /** @var */
    private $_event;
    /** @var  int */
    private $_errno;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (!$data['wallet']) {
            throw new InvalidParamException('wallet empty');
        }
        if (!$data['api_key']) {
            throw new InvalidParamException('api_key empty');
        }
        /*if (!$data['secret_word1']) {
            throw new InvalidParamException('secret_word1 empty');
        }
        if (!$data['secret_word2']) {
            throw new InvalidParamException('secret_word2 empty');
        }
        if (!$data['merchant_id']) {
            throw new InvalidParamException('merchant_id empty');
        }*/

        $this->_wallet = $data['wallet'];
        $this->_api_key = $data['api_key'];
        $this->_merchant_id = $data['merchant_id'] ?? '';
        $this->_secret_word1 = $data['secret_word1'] ?? '';
        $this->_secret_word2 = $data['secret_word2'] ?? '';
        $this->_event = $data['event'] ?? null;
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
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = static::_validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        return [
            'data' => [
                'fields' => static::_getFields($params['currency'], $params['payment_method'], self::WAY_DEPOSIT),
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
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        if (!empty($transaction->commission_payer)) {
            $this->logger->log($transaction->id, 100, self::ERROR_COMMISSION_PAYER);
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = static::_validateTransaction($transaction['currency'], $transaction->payment_method, $transaction['amount'], self::WAY_DEPOSIT);

        if ($validate !== true) {
            $this->logger->log($transaction->id, 100, $validate);
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
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $redirectParams = [
            'm' => $this->_merchant_id,
            'oa' => $transaction->amount,
            'o' => $transaction->id,
            'i' => static::_getCurrencyId($transaction->currency, $transaction->payment_method)
        ];

        $signArray = [
            $this->_merchant_id,
            $transaction->amount,
            $this->_secret_word1,
            $transaction->id
        ];

        $redirectParams['s'] = md5(implode(':', $signArray));

        $transaction->query_data = json_encode($redirectParams);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        Yii::info(['query' => $redirectParams, 'result' => []], 'payment-freekassa-invoice');

        $transaction->refund = $transaction->amount;
        $transaction->status = self::STATUS_CREATED;
        $transaction->save(false);

        $frontend_link = rtrim(Yii::$app->params['frontend_link'], '/');

        $answer['redirect'] = [
            'method' => 'URL',
            'url' => $frontend_link . '/freekassa/' . $transaction->id,
            'params' => []
        ];

        $answer['data'] = $transaction::getDepositAnswer($transaction);

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
        $answer = [];

        $validate = static::_validateTransaction($data['currency'], $data['payment_method'], $data['amount'], self::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $check = $this->_checkWallet($data['currency'], $data['amount']);

        if ($check !== true) {
            return $check;
        }

        $fields = static::_getFields($data['currency'], $data['payment_method'], self::WAY_WITHDRAW);

        $answer['data'] = [
            'fields' => $fields,
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'merchant_write_off' => null,
            'buyer_receive' => null
        ];

        if ($commission = static::_getCommission($data['amount'], $data['currency'], $data['payment_method'])) {
            if ($data['commission_payer'] === self::COMMISSION_BUYER) {
                $answer['data']['buyer_receive'] = $data['amount'] - $commission;
            }

            if ($data['commission_payer'] === self::COMMISSION_MERCHANT) {
                $answer['data']['buyer_receive'] = $data['amount'];
            }
        }

        return $answer;
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
            $this->logger->log($transaction->id, 100, self::ERROR_COMMISSION_PAYER);
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = static::_validateTransaction($transaction->currency, $transaction->payment_method, $transaction->amount, self::WAY_WITHDRAW);

        if ($validate !== true) {
            $this->logger->log($transaction->id, 100, $validate);
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $check = $this->_checkWallet($transaction->currency, $transaction->amount);

        if ($check !== true) {
            //$transaction->delete();
            return $check;
        }

        $currency = static::_getCurrencyId($transaction->currency, $transaction->payment_method);

        $query = [
            'wallet_id' => $this->_wallet,
            'amount' => $transaction->amount,
            'desc' => $transaction->comment,
            'currency' => $currency,
            'action' => 'cashout',
        ];

        $requisites = json_decode($transaction->requisites, true);

        $message = self::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_DEPOSIT);

        if ($message !== true) {
            $this->logger->log($transaction->id, 100, $message);
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        if (!empty($requisites)) {
            foreach ($requisites as $key => $fields) {
                $requisites[$key] = urldecode($fields);
            }
            $query = array_merge($query, $requisites);
        }

        $query['sign'] = md5($this->_wallet . $currency . $transaction->amount . $query['purse'] . $this->_api_key);

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $result = $this->_query(static::API_WALLET_URL, $query);

        $transaction->result_data = json_encode($result);

        Yii::info(['query' => $query, 'result' => $result], 'payment-freekassa-withdraw');

        $this->logger->log($transaction->id, 3, $transaction->result_data);

        $answer = [];

        if (isset($result['status']) && $result['status'] === 'info') {
            if (isset($result['data']['payment_id'])) {
                $transaction->external_id = $result['data']['payment_id'];
                $transaction->status = self::STATUS_CREATED;

                if ($commission = static::_getCommission($transaction->amount, $transaction->currency, $transaction->payment_method)) {
                    $transaction->receive = $transaction->amount - $commission;
                }

                $transaction->save(false);

                $answer['data'] = $transaction::getWithdrawAnswer($transaction);

            }
        } else if ($this->_errno == CURLE_OPERATION_TIMEOUTED) {
            $transaction->status = self::STATUS_TIMEOUT;
            $transaction->save(false);
            //$params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else if ($this->_errno != CURLE_OK) {
            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->result_data = $this->_errno;
            $transaction->save(false);
            //$params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = isset($result['desc']) ? mb_convert_encoding($result['desc'], "utf-8") : self::ERROR_OCCURRED;

            Yii::error($message, 'payment-freekassa-withdraw');
            $this->logger->log($transaction->id, 100, $answer);
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
        if (in_array($transaction->status, self::getNotFinalStatuses())) {

            if ($transaction->way === self::WAY_DEPOSIT) {

                $query = [
                    'order_id' => $transaction->id,
                    'merchant_id' => $this->_merchant_id,
                    's' => md5($this->_merchant_id . $this->_secret_word2),
                    'action' => 'check_order_status',
                ];

                $response = $this->_query(static::API_URL, $query, false);

                if (isset($response['answer']) && $response['answer'] === 'info' && isset($response['status'])) {
                    if (static::_isFinal($response['status'])) {
                        if (static::_isSuccessStateCustom($response['status'])) {
                            $transaction->status = self::STATUS_SUCCESS;
                        } elseif ($response['status'] === 'canceled') {
                            $transaction->status = self::STATUS_CANCEL;
                        }
                    } else {
                        $transaction->status = self::STATUS_PENDING;
                    }

                    if (!$transaction->external_id && isset($response['intid'])) {
                        $transaction->external_id = $response['intid'];
                    }

                    $transaction->save(false);

                }
                
                $this->logger->log($transaction->id, 8, $response);

                return true;
            }

            if ($transaction->way === self::WAY_WITHDRAW) {
                $query = [
                    'wallet_id' => $this->_wallet,
                    'payment_id' => $transaction->external_id,
                    'sign' => md5($this->_wallet . $transaction->external_id . $this->_api_key),
                    'action' => 'get_payment_status',
                ];

                $response = $this->_query(static::API_WALLET_URL, $query);

                if (isset($response['data']) && $response['data']['status']) {
                    if (static::_isFinal($response['data']['status'])) {
                        if (static::_isSuccessStateCustom($response['data']['status'])) {
                            $transaction->status = self::STATUS_SUCCESS;
                        } elseif ($response['data']['status'] === 'Canceled') {
                            $transaction->status = self::STATUS_CANCEL;
                        }
                    } else {
                        $transaction->status = self::STATUS_PENDING;
                    }

                    $transaction->save(false);
                }

                $this->logger->log($transaction->id, 8, $response);

                return true;
            }
        }

        return false;
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

        if (!self::_checkIP()) {
            $this->logAndDie(
                'FreeKassa receive() from undefined server',
                'IP = ' . Yii::$app->request->getUserIP(),
                "Undefined server\n"
            );
        }

        if (isset($receiveData['currency']) && in_array($receiveData['currency'], ['btc', 'ltc', 'eth'])) {
            return $this->_receiveCryptoCurrency($transaction, $receiveData);
        } else {
            return $this->_receiveNormalCurrency($transaction, $receiveData, $data['currency']);
        }
    }

    /**
     * @param Transaction $transaction
     * @param array $params
     * @return bool
     */
    private function _receiveCryptoCurrency($transaction, array $params)
    {
        if (!$this->_checkCryptoSign($params)) {
            return false;
        }

        $transaction->external_id = $params['transaction_id'];

        if ($this->_checkDoubleSpend($params['transaction_id'])) {
            $transaction->status = self::STATUS_DSPEND;
            $transaction->save(false);

            return true;
        }

        $transaction->refund = $params['amount'];

        $confs = $params['confirmations'];

        if ($confs == 0) {
            $transaction->status = self::STATUS_PENDING;
            /*$txFeePerByte = $this->_calcFee($params['transaction_id']);

            $minFeePerByte = $this->_getFee('min');

            if ($txFeePerByte != false && $txFeePerByte <= $minFeePerByte) {
                if ($this->_event) {
                    $event = new $this->_event;
                    $event->brand_id = $transaction->brand_id;
                    $event->type = Dispatch::ALERT_TOO_LOW_FEE;
                    $event->message = [
                        'text' => "Too low fee per byte {$txFeePerByte} (min {$minFeePerByte})",
                        'transaction' => Transaction::getNoteStructure($transaction)
                    ];

                    Yii::$app->notify->trigger(Notification::EVENT_SEND_ALERT, $event);

                    Yii::warning($event->message, 'payment-blockchain-receive');
                }
            }*/

        } elseif ($confs > 0 && $confs < $this->_NOC) {
            $transaction->status = self::STATUS_UNCONFIRMED;
        } elseif ($confs >= $this->_NOC) {

            if ($transaction->amount == $transaction->refund) {
                $transaction->status = self::STATUS_SUCCESS;
            } else {
                $transaction->status = self::STATUS_MISPAID;
            }

            $transaction->save(false);

            return true;
        }

        $transaction->save(false);

        return false;
    }

    /**
     * @param string $type
     * @return bool
     */
    private function _getFee(string $type = 'regular')
    {
        $types = ['regular', 'priority', 'min', 'max'];

        if (!in_array($type, $types)) {
            return false;
        }

        $request = (new QueryBuilder('https://api.blockchain.info/mempool/fees'))
            ->send();

        $result = $request->getResponse(true);

        if (in_array($type, ['min', 'max'])) {
            return $result['limits'][$type];
        } else {
            return $result[$type] ?? false;
        }
    }

    /**
     * Calc transaction fee
     * @param string $tx
     * @return bool|float|int
     */
    private function _calcFee(string $tx)
    {
        $url = self::BLOCKCHAIN_URL . 'tx/' . $tx;

        $request = (new QueryBuilder($url))
            ->setParams(['format' => 'json'])
            ->send();

        $result = $request->getResponse(true);

        $input = 0;
        $output = 0;

        foreach ($result['inputs'] as $in) {
            if (isset($in['prev_out']['value'])) {
                $input += (int)$in['prev_out']['value'];
            } else {
                return false;
            }
        }

        foreach ($result['out'] as $out) {
            if (isset($out['value'])) {
                $output += (int)$out['value'];
            } else {
                return false;
            }
        }

        $fee = $input - $output;

        if ($fee == 0) {
            return false;
        }

        return $fee / $result['size'];
    }

    /**
     * @param string $tr_hash
     * @return bool
     */
    private function _checkDoubleSpend(string $tr_hash)
    {
        $url = self::BLOCKCHAIN_URL . 'tx/' . $tr_hash;

        $request = (new QueryBuilder($url))
            ->setParams(['format' => 'json'])
            ->send();

        $result = $request->getResponse(true);

        return (isset($result['double_spend']) && $result['double_spend'] == true) || (isset($result['rbf']) && $result['rbf'] == true);
    }

    /**
     * @param Transaction $transaction
     * @param array $params
     * @param $currency
     * @return bool
     */
    private function _receiveNormalCurrency($transaction, array $params, $currency)
    {
        $need = ['AMOUNT', 'CUR_ID'];

        if (!self::checkRequiredParams($need, $params)) {
            Yii::error([
                'receive_data' => $params,
                'need' => $need
            ], 'payment-freekassa-receive');
            return false;
        }

        if (!$this->_checkReceivedSign($params)) {
            return false;
        }

        if (!$transaction->external_id) {
            $transaction->external_id = $params['intid'] ?? 0;
            $transaction->save(false);
        }

        if ($transaction->amount != $params['AMOUNT']) {
            $this->logAndDie(
                'FreeKassa receive() transaction amount not equal received amount',
                "Transaction amount = {$transaction->amount}\nreceived amount = {$params['AMOUNT']}",
                "Transaction amount not equal received amount\n"
            );
        }

        $currency_id = static::_getCurrencyID($currency, $transaction->payment_method);

        if ($currency_id != $params['CUR_ID']) {
            $this->logAndDie(
                'FreeKassa receive() different currency',
                "Casino currency = {$currency}\nreceived currency = {$params['CUR_ID']}",
                "Different currency\n"
            );
        }

        $transaction->status = self::STATUS_SUCCESS;
        $transaction->save(false);

        return true;
    }

    /**
     * @param string $currency
     * @param int|float $amount
     * @return array|bool
     */
    private function _checkWallet(string $currency, $amount)
    {
        $query = [
            'wallet_id' => $this->_wallet,
            'sign' => md5($this->_wallet . $this->_api_key),
            'action' => 'get_balance'
        ];

        $result = $this->_query(static::API_WALLET_URL, $query);

        if (isset($result['status']) && $result['status'] === 'info') {
            $currencies = $result['data'];

            if ($currency === 'RUB') {
                $cur = 'RUR';
            } else {
                $cur = $currency;
            }

            if (!isset($currencies[$cur])) {
                return [
                    'status' => 'error',
                    'message' => "Wallet doesn't support currency '{$currency}'"
                ];
            }

            if ($currencies[$cur] <= $amount) {
                return [
                    'status' => 'error',
                    'message' => "Insufficient funds",
                    'code' => ApiError::LOW_BALANCE
                ];
            }
        }

        return true;
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
        if (!$post && count($params) > 0) {
            $url .= "?" . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $response = trim(curl_exec($ch));
        $this->_errno = curl_errno($ch);

        curl_close($ch);

        if ($response === false) {
            return false;
        } else {
            $responseJson = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $responseJson;
            } else {
                try {
                    return json_decode(json_encode(new \SimpleXMLElement($response)), true);
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
    }

    /**
     * @param array $params
     * @return bool
     */
    private function _checkReceivedSign(array $params): bool
    {
        $signArray = [
            $this->_merchant_id,
            $params['AMOUNT'],
            $this->_secret_word2,
            $params['MERCHANT_ORDER_ID']
        ];

        $expectedSign = md5(implode(':', $signArray));

        if ($params['SIGN'] !== $expectedSign) {
            Yii::error("Free-kassa receive() wrong sign is received: expectedSign = {$expectedSign} \nSign = {$params['SIGN']}", 'payment-freekassa');
        } else {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private static function _checkIP(): bool
    {
        if (YII_ENV && YII_ENV === 'dev') {
            self::$_allow_ip[] = '127.0.0.1';
            self::$_allow_ip[] = '5.9.49.227';
        }

        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            return in_array($_SERVER['HTTP_X_REAL_IP'], self::$_allow_ip);
        }

        return in_array($_SERVER['REMOTE_ADDR'], self::$_allow_ip);
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
     * Getting transaction id
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['MERCHANT_ORDER_ID'] ?? 0;
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
        return isset($data['MERCHANT_ORDER_ID']) ? ['id' => $data['MERCHANT_ORDER_ID']] : [];
    }

    /**
     * Getting success answer for stopping send callback data
     * @return string
     */
    public static function getSuccessAnswer()
    {
        return 'YES';
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
     * Validation transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    private static function _validateTransaction(string $currency, string $method, float $amount, string $way)
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
        if (1 >= $amount || 1000000 <= $amount) {
            if (1 >= $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            if (1000000 <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;

            return "Amount should to be more than '1' and less than '1000000'";
        }

        return true;
    }

    /**
     * Checking final status
     * @param string $status
     * @return bool
     */
    private static function _isFinal(string $status): bool
    {
        $statuses = [
            'new' => false,
            'in process' => false,
            'canceled' => true,
            'completed' => true,
            'error' => true
        ];

        return $statuses[strtolower($status)] ?? false;
    }

    /**
     * Checking success status
     * @param string $status
     * @return bool
     */
    private static function _isSuccessStateCustom(string $status): bool
    {
        return strtolower($status) === 'completed';
    }

    /**
     * @param string $currency
     * @param string $method
     * @return int
     */
    private static function _getCurrencyID(string $currency, string $method)
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$method]['currency_id'] ?? 1;
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
     * @param float $amount
     * @param string $currency
     * @param string $payment_method
     * @return float|null
     */
    private static function _getCommission(float $amount, string $currency, string $payment_method)
    {
        $currencies = static::getSupportedCurrencies();

        if (!$value = $currencies[$currency][$payment_method]['commission']['value']) {
            return null;
        }

        $measurement = $currencies[$currency][$payment_method]['commission']['measurement'];

        switch ($measurement) {
            case 'percent':
                return round($amount * $value / 100, 2);
            case 'currency':
                return $value;
        }

        return null;
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
                if (!in_array($field, $requisites) && in_array($currency, $methods[$method][$way][$field]['currencies'])) {
                    return "Required param '{$field}' not found";
                }
            }
        }

        return true;
    }

    /**
     * Get new address for user
     * @param $buyer_id
     * @param $callback_url
     * @param $brand_id
     * @param $currency
     * @return array
     */
    public function getAddress($buyer_id, $callback_url, $brand_id, $currency): array
    {
        switch ($currency) {
            case 'BTC':
                $action = 'create_btc_address'; break;
            case 'LTC':
                $action = 'create_ltc_address'; break;
            case 'ETH':
                $action = 'create_eth_address'; break;
            default:
                return [
                    'status' => 'error',
                    'message' => 'Currency is not supported'
                ];
        }
        $query = [
            'wallet_id' => $this->_wallet,
            'sign' => md5($this->_wallet . $this->_api_key),
            'action' => $action,
        ];

        $request = (new QueryBuilder(self::API_WALLET_URL))
            ->setParams($query)
            ->asPost()
            ->send();

        $result = $request->getResponse(true);

        Yii::info(['query' => $query, 'result' => $result], 'payment-freekassa-get_address');

        if (isset($result['data']['address'])) {
            return [
                'data' => [
                    'address' => $result['data']['address']
                ],
            ];
        } else {
            $answer['status'] = 'error';

            Yii::warning(['query' => $query, 'request' => $request], 'payment-freekassa-getAddress');

            if (isset($result['desc'])) {
                $answer['message'] = $result['desc'];
            } else {
                $answer['message'] = self::ERROR_OCCURRED;
            }

            return $answer;
        }
    }

    /**
     * Fill incoming transaction
     * @param $paymentSystemId
     * @param ActiveQuery $userAddress
     * @param $receiveData
     * @return array|bool
     */
    public static function fillTransaction($paymentSystemId, $userAddress, $receiveData)
    {
        if (!self::_checkIP()) {
            return false;
        }

        $user = $userAddress->where([
            'address' => $receiveData['address'],
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
            'amount' => $receiveData['amount'],
            'payment_method' => 'bitcoin',
            'comment' => "Deposit from user #{$user['user_id']}",
            'merchant_transaction_id' => $receiveData['transaction_id'],
            'external_id' => $receiveData['transaction_id'],
            'commission_payer' => self::COMMISSION_BUYER,
            'commission' => $receiveData['fee'],
            'buyer_id' => $user['user_id'],
            'status' => self::STATUS_CREATED,
            'urls' => json_encode($urls)
        ];
    }

    /**
     * @param $receiveData
     * @return bool
     */
    private function _checkCryptoSign($receiveData): bool
    {
        if (!self::checkRequiredParams(['wallet_id', 'address', 'transaction_id', 'amount', 'fee', 'confirmations', 'sign', 'date'], $receiveData)) {
            Yii::warning(['message' => "Required parameters not found", 'data' => $receiveData], 'payment-freekassa-receive');
            return false;
        }

        // wallet_id, address, transaction_id, amount, fee, confirmations, date и API ключа

        $mySign = md5($receiveData['wallet_id'] . $receiveData['address'] . $receiveData['transaction_id'] . $receiveData['amount'] . $receiveData['fee'] . $receiveData['confirmations'] . $receiveData['date'] . $this->_api_key);

        if ($receiveData['sign'] !== $mySign) {
            Yii::warning([
                'message' => "Checking sign is failed",
                'data' => $receiveData,
            ], 'payment-freekassa-receive');
            return false;
        }

        return true;
    }
}