<?php

namespace pps\blockchain;

use api\classes\ApiError;
use backend\components\Notification;
use backend\models\Dispatch;
use common\models\Transaction;
use pps\querybuilder\QueryBuilder;
use pps\payment\Payment;
use Yii;
use yii\base\Event;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\ICryptoCurrency;

/**
 * Class Blockchain
 * @package pps\blockchain
 */
class Blockchain extends Payment implements ICryptoCurrency
{
    const BLOCKCHAIN_URL = 'https://blockchain.info/';
    const API_URL = 'https://api.blockchain.info/v2/';
    const TO_SAT = 100000000; // Coefficient for convert bitcoint to satoshi

    /**
     * @var string
     */
    private $_wallet_url;
    /**
     * @var Event
     */
    private $_event;
    /**
     * @var int
     */
    private $_NOC;
    /**
     * @var int
     */
    private $_fee;
    /**
     * @var int
     */
    private $_min_fee_per_byte;
    /**
     * @var int
     */
    private $_auto_min_fee_per_byte;
    /**
     * @var int
     */
    private $_max_gap;
    /**
     * @var Transaction
     */
    private $_transaction;
    /**
     * @var string
     */
    private $_xpub;
    /**
     * @var string
     */
    private $_api_key;
    /**
     * @var array
     */
    private static $_allow_ip = [
        '104.16.54.3',
        '5.9.49.227',
    ];
    /**
     * For wallet API
     * @var string
     */
    private $_guid, $_password, $_second_password;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['wallet_url'])) {
            throw new InvalidParamException('"wallet_url" do not isset in main config');
        }
        if (isset($data['confirmations']) && !is_int($data['confirmations'])) {
            throw new InvalidParamException('"confirmations" should be integer');
        }
        if (isset($data['fee']) && !is_int($data['fee'])) {
            throw new InvalidParamException('"fee" should be integer');
        }
        if (empty($data['xpub'])) {
            throw new InvalidParamException('"xpub" empty');
        }
        if (empty($data['api_key'])) {
            throw new InvalidParamException('"api_key" empty');
        }

        $this->_wallet_url = rtrim($data['wallet_url'], '/');
        $this->_NOC = $data['confirmations'] ?? 6;
        $this->_fee = $data['fee'] ?? 10000;
        $this->_min_fee_per_byte = $data['min_fee_per_byte'] ?? 4;
        $this->_auto_min_fee_per_byte = $data['auto_min_fee_per_byte'] ?? false;
        $this->_max_gap = $data['max_gap'] ?? 15;

        $this->_xpub = $data['xpub'];
        $this->_api_key = $data['api_key'];
        // For wallet API
        $this->_guid = $data['guid'] ?? null;
        $this->_password = $data['password'] ?? null;
        $this->_second_password = $data['second_password'] ?? null;

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
        return [
            'status' => 'error',
            'message' => self::ERROR_METHOD
        ];
    }

    /**
     * Invoicing for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
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
        $this->_transaction = $data['transaction'];
        $receiveData = $data['receive_data'];

        if (in_array($this->_transaction->status, self::getFinalStatuses())) {
            return true;
        }

        if (!self::checkRequiredParams(['transaction_hash', 'sign', 'value', 'confirmations', 'address'], $receiveData)) {
            //$this->_transaction->delete();
            return false;
        }

        if (!self::_checkIP()) {
            $this->logAndDie(
                'Blockchain receive() from undefined server',
                'IP = ' . $_SERVER['REMOTE_ADDR'] ?? 'undefined',
                "Undefined server\n"
            );
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        $this->_transaction->external_id = $receiveData['transaction_hash'];

        if ($this->_checkDoubleSpend($receiveData['transaction_hash'])) {
            $this->_transaction->status = self::STATUS_DSPEND;
            $this->_transaction->save(false);

            return true;
        }

        $this->_transaction->refund = $receiveData['value'] / self::TO_SAT;

        $confs = $receiveData['confirmations'];

        if ($confs == 0) {
            $this->_transaction->status = self::STATUS_PENDING;
            $txFeePerByte = $this->_calcFee($receiveData['transaction_hash']);

            if ($this->_auto_min_fee_per_byte) {
                $minFeePerByte = $this->_getFee('min');
            } else {
                $minFeePerByte = $this->_min_fee_per_byte;
            }

            if ($txFeePerByte != false && $txFeePerByte <= $minFeePerByte) {
                if ($this->_event) {
                    $event = new $this->_event;
                    $event->brand_id = $this->_transaction->brand_id;
                    $event->type = Dispatch::ALERT_TOO_LOW_FEE;
                    $event->message = [
                        'text' => "Too low fee per byte {$txFeePerByte} (min {$minFeePerByte})",
                        'transaction' => Transaction::getNoteStructure($this->_transaction)
                    ];
                    Yii::$app->notify->trigger(Notification::EVENT_SEND_ALERT, $event);
                    Yii::warning($event->message, 'payment-blockchain-receive');
                }
            }

        } elseif ($confs > 0 && $confs < $this->_NOC) {
            $this->_transaction->status = self::STATUS_UNCONFIRMED;
        } elseif ($confs >= $this->_NOC) {

            if ($this->_transaction->amount == $this->_transaction->refund) {
                $this->_transaction->status = self::STATUS_SUCCESS;
            } else {
                $this->_transaction->status = self::STATUS_MISPAID;
            }
            $this->_transaction->save(false);

            return true;
        }

        $this->_transaction->save(false);

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
        $validate = self::_validateTransaction($params['currency'], $params['payment_method'], $params['amount'], 'withdraw');

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $result = [
            'data' => [
                'fields' => self::_getFields($params['currency'], $params['payment_method'], 'withdraw'),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => $params['amount'],
            ]
        ];

        if ($params['commission_payer'] === self::COMMISSION_MERCHANT) {
            $result['data']['buyer_receive'] = $params['amount'];
        } else {
            $result['data']['buyer_receive'] = round($params['amount']  - $this->_fee/self::TO_SAT, 8);
        }

        return $result;
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

        $validate = self::_validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, 'withdraw');

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
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $requests['m_out']->transaction_id = $this->_transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        $index = $this->_getAccountIndex();

        if ($index === false) {
            //$this->_transaction->delete();
            return [
                'status' => 'error',
                'message' => 'Account index not found'
            ];
        }

        $amount = $this->_transaction->amount * self::TO_SAT;

        if (!$purseAmount = $this->_getBalance()) {
            return [
                'status' => 'error',
                'message' => 'Error getting wallet balance'
            ];
        }

        if ($purseAmount <= $amount) {
            //$this->_transaction->delete();
            return [
                'status' => 'error',
                'message' => "Insufficient funds"
            ];
        }

        $fee = $this->_fee;

        if ($this->_transaction->commission_payer === self::COMMISSION_MERCHANT) {
            $amountWithFee = $amount;
        } else {
            $amountWithFee = $amount - $fee;
        }

        $query = [
            'password' => $this->_password,
            'amount' => $amountWithFee,
            'from' => $index,
            'fee' => $fee,
        ];

        if (!empty($this->_second_password)) {
            $query['second_password'] = $this->_second_password;
        }

        $requisites = json_decode($this->_transaction->requisites, true);

        if (!isset($requisites['address'])) {
            return [
                'status' => 'error',
                'message' => "Required param 'address' not found"
            ];
        }

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites);
        }

        $this->_transaction->query_data = json_encode($query);

        $url = "{$this->_wallet_url}/merchant/{$this->_guid}/payment";

        $request = (new QueryBuilder($url))
            ->setParams($query)
            ->setOptions([
                CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
                CURLOPT_TIMEOUT => self::$timeout,
            ])
            ->send();

        $result = $request->getResponse(true);

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $this->_transaction->result_data = json_encode($result);

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-blockchain-withdraw');

        if (isset($result['success']) && $result['success'] == true) {

            $this->_transaction->external_id = $result['txid'];
            $this->_transaction->receive = round($result['amounts'][0] / self::TO_SAT, 8);
            $this->_transaction->write_off = round(($result['fee'] + $result['amounts'][0]) / self::TO_SAT, 8);
            $this->_transaction->status = self::STATUS_CREATED;
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
            $this->_transaction->save();

            $answer = ['status' => 'error'];

            if (isset($result['message'])) {
                $answer['message'] = $result['message'];
            } else {
                $answer['message'] = self::ERROR_OCCURRED;
            }

            $message = "Request url = {$url}";
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-blockchain-withdraw');
        }

        return $answer;
    }

    /**
     * TODO speed up
     * Too slow method
     * @param object $transaction
     * @param null $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if (in_array($transaction->status, self::getNotFinalStatuses()) && $transaction->way === 'withdraw') {
            $n = 0;

            try_again:

            $rawtxRequest = (new QueryBuilder(self::BLOCKCHAIN_URL . "rawtx/{$transaction->external_id}"))->send();

            if (($rawtxRequest->getInfo())['http_code'] == 500) {
                return false;
            }

            if (($rawtxRequest->getInfo())['http_code'] == 429) {
                sleep(11);
                if ($n > 2) {
                    return false;
                } else {
                    $n++;
                    goto try_again;
                }
            }

            $rawtx = $rawtxRequest->getResponse(true);
            //sleep(11);
            $getblockcountRequest = (new QueryBuilder(self::BLOCKCHAIN_URL . "q/getblockcount/{$transaction->external_id}"))->send();
            $getblockcount = $getblockcountRequest->getResponse();

            $countConfirmations = $getblockcount - $rawtx['block_height'] + 1;

            if ($countConfirmations >= $this->_NOC) {
                $this->_transaction->status = self::STATUS_SUCCESS;
            } else {
                $this->_transaction->status = self::STATUS_PENDING;
            }

            $transaction->save(false);

            if ($model_req) {
                $model_req->transaction_id = $this->_transaction->id;
                $model_req->data = json_encode([$rawtx, $getblockcount]);
                $model_req->type = 8;
                $model_req->save(false);

                Yii::info([$rawtx, $getblockcount], 'payment-blockchain');
            }

            return true;
        }

        return false;
    }

    /**
     * @param int|string $buyer_id
     * @param string $callback_url
     * @param $brand_id
     * @return array
     */
    public function getAddress($buyer_id, $callback_url, $brand_id, $currency): array
    {
        $this->_checkGapLimit($brand_id);

        $query = [
            'xpub' => $this->_xpub,
            'key' => $this->_api_key,
            'callback' => $callback_url . (strpos($callback_url, '?') ? '&' : '?')
                . 'sign=' . $this->_getSign($buyer_id)
        ];

        $request = (new QueryBuilder(self::API_URL . 'receive'))
            ->setParams($query)
            ->send();

        $result = $request->getResponse(true);

        Yii::info(['query' => $query, 'result' => $result], 'payment-blockchain-get_address');

        if (isset($result['address'])) {
            return [
                'data' => [
                    'address' => $result['address']
                ],
            ];
        } else {
            $answer['status'] = 'error';

            Yii::warning(['query' => $query, 'request' => $request], 'payment-blockchain-getAddress');

            if (isset($result['message'])) {
                $answer['message'] = json_encode($result['message']);
            } else {
                $answer['message'] = self::ERROR_OCCURRED;
            }

            return $answer;
        }
    }

    /**
     * @param string $currency
     * @param float $price
     * @return bool
     */
    private function _convertPrice(string $currency, float $price)
    {
        $request = (new QueryBuilder(self::BLOCKCHAIN_URL . 'ticker'))->send();

        $result = $request->getResponse(true);

        if ($result && isset($result[$currency])) {
            return $result[$currency]['15m'] * $price;
        }

        return false;
    }

    /**
     * Send alert if number of allowed addresses ends
     * @param int $brand_id
     */
    private function _checkGapLimit(int $brand_id)
    {
        $request = (new QueryBuilder(self::API_URL . 'receive/checkgap'))
            ->setParams([
                'xpub' => $this->_xpub,
                'key' => $this->_api_key,
            ])
            ->send();

        $result = $request->getResponse(true);

        Yii::info(['result' => $result], 'payment-blockchain-gap_limit');

        if ($result['gap'] >= $this->_max_gap) {
            if ($this->_event) {
                $event = new $this->_event;
                $event->brand_id = $brand_id;
                $event->type = Dispatch::ALERT_GAP_LIMIT;
                $event->message = [
                    'text' => "Too low allowed addresses. Gap = {$result['gap']}"
                ];
                Yii::$app->notify->trigger(Notification::EVENT_SEND_ALERT, $event);
            }
        }
    }

    /**
     * @param $buyer_id
     * @return string
     */
    private function _getSign($buyer_id): string
    {
        return md5("{$this->_api_key}:{$buyer_id}:{$this->_api_key}");
    }

    /**
     * @param array $params
     * @return bool
     */
    private function _checkReceivedSign(array $params): bool
    {
        $sign = $params['sign'];

        $expectedSign = $this->_getSign($this->_transaction->buyer_id);
        // If sign is wrong
        if ($sign != $expectedSign) {
            Yii::error("Blockchain receive() wrong sign is received: expectedSign = {$expectedSign}\nSign = {$sign}",'payment-blockchain-receive');
            return false;
        }

        return true;
    }

    /**
     * @return bool|int
     */
    private function _getBalance()
    {
        $url = "{$this->_wallet_url}/merchant/{$this->_guid}/accounts/{$this->_xpub}/balance";

        $request = (new QueryBuilder($url))
            ->setParams([
                'password' => $this->_password,
                'api_code' => $this->_api_key,
            ])
            ->send();

        $result = $request->getResponse(true);

        return $result['balance'] ?? false;
    }

    /**
     * Get account index for withdraw
     * @return bool
     */
    private function _getAccountIndex()
    {
        $url = "$this->_wallet_url/merchant/{$this->_guid}/accounts";

        $request = (new QueryBuilder($url))
            ->setParams([
                'password' => $this->_password,
                'api_code' => $this->_api_key,
            ])
            ->send();

        $accounts = $request->getResponse(true);

        if (!empty($accounts) && is_array($accounts)) {
            foreach ($accounts as $account) {
                if ($account['extendedPublicKey'] == $this->_xpub) {
                    return $account['index'];
                }
            }
        }

        return false;
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
     * @return bool
     */
    private static function _checkIP(): bool
    {
        if (defined('YII_DEBUG') && YII_DEBUG === true) {
            self::$_allow_ip[] = '127.0.0.1';
        }

        return in_array($_SERVER['REMOTE_ADDR'] ?? '', self::$_allow_ip);
    }

    /**
     * @param $paymentSystemId
     * @param $userAddress
     * @param $receive_data
     * @return array|bool
     */
    public static function fillTransaction($paymentSystemId, $userAddress, $receive_data)
    {
        if (!self::checkRequiredParams(['address', 'value', 'transaction_hash', 'sign', 'confirmations'], $receive_data)) {
            Yii::warning(['message' => "Required parameters not found", 'data' => $receive_data], 'payment-blockchain-receive');
            return false;
        }

        if (!self::_checkIP()) {
            return false;
        }

        $user = $userAddress->where([
            'address' => $receive_data['address'],
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
            'amount' => round($receive_data['value'] / self::TO_SAT, 8),
            'payment_method' => 'bitcoin',
            'comment' => "Deposit from user #{$user['user_id']}",
            'merchant_transaction_id' => $receive_data['transaction_hash'],
            'external_id' => $receive_data['transaction_hash'],
            'commission_payer' => self::COMMISSION_BUYER,
            'buyer_id' => $user['user_id'],
            'status' => self::STATUS_CREATED,
            'urls' => json_encode($urls)
        ];
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
     * Getting query for search transaction after success or fail transaction
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return [];
    }

    /**
     * Getting transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return false;
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
}
