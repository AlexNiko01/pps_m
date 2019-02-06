<?php

namespace pps\ecommpay;

use api\classes\ApiError;
use pps\querybuilder\QueryBuilder;
use pps\payment\Payment;
use Yii;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class Ecommpay
 * @package pps\ecommpay
 */
class Ecommpay extends Payment
{
    const API_URL = 'https://terminal-sandbox.accentpay.com/';
    const GATE_URL = 'https://gate-sandbox.accentpay.com/';
    //const API_URL = 'https://terminal.accentpay.com/';
    //const GATE_URL = 'https://gate.accentpay.com/';

    /**
     * @var object
     */
    private $_transaction;
    /**
     * @var string
     */
    private $_site_id;
    /**
     * @var string
     */
    private $_salt;
    /**
     * @var array
     */
    private $_allow_ip;
    /**
     * @var string
     */
    private $_lifetime = '2 hour';


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (!$data['site_id']) {
            throw new InvalidParamException('site_id empty');
        }
        if (!$data['salt']) {
            throw new InvalidParamException('salt empty');
        }

        $this->_site_id = $data['site_id'];
        $this->_salt = $data['salt'];

        $this->_allow_ip = [
            '5.9.49.227'
        ];

        if (YII_ENV && YII_ENV === 'dev') {
            $this->_allow_ip[] = '127.0.0.1';
        }
    }

    /**
     * TODO to check it
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
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
                'fields' => static::_getFields($params['currency'], $params['payment_method'], 'deposit'),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_refund' => null,
                'buyer_write_off' => null,
            ]
        ];
    }

    /**
     * TODO check it
     * Invoicing for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        $this->_transaction = $params['transaction'];
        $requests = $params['requests'];

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
                'message' => 'transaction_id is not unique'
            ];
        }

        $requests['m_out']->transaction_id = $this->_transaction->id;
        $requests['m_out']->data           = $requests['merchant'];
        $requests['m_out']->type           = 1;
        $requests['m_out']->save(false);

        $query = [
            'action' => 'create_invoice',
            'site_id' => $this->_site_id,
            'external_id' => $this->_transaction->id,
            'amount' => $this->_transaction->amount * 100,
            'currency' => $this->_transaction->currency,
            'customer_ip' => Yii::$app->request->getUserIP(),
            'comment' => $this->_transaction->comment,
            'force_disable_callback' => 1,
            'first_callback_delay' => 0
        ];

        $hidden = static::_getHiddenFields($this->_transaction->currency, $this->_transaction->payment_method, 'deposit', $params);

        $requisites = json_decode($this->_transaction->requisites, true);

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites, $hidden);
        } else {
            $query = array_merge($query, $hidden);
        }

        $query['signature'] = $this->_getSign($query);

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $request = (new QueryBuilder($this->_getUrl($this->_transaction->currency, $this->_transaction->payment_method)))
            ->setParams($query)
            ->asPost()
            ->setOptions([
                CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
                CURLOPT_TIMEOUT => self::$timeout,
            ])
            ->send();
        $result = $request->getResponse(true);

        $this->_transaction->result_data = json_encode($result);

        //return [$result];

        $requests['pa_in']->transaction_id = $this->_transaction->id;
        $requests['pa_in']->data = $this->_transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-ecommpay-invoice');

        if ($this->_isSuccess($result)) {

            $this->_transaction->refund = $result['amount'] / 100;
            $this->_transaction->external_id = $result['transaction_id'];
            $this->_transaction->status = self::STATUS_CREATED;

            // Purchase with 3DS
            if (isset($result['pa_req'])) {
                $query3DS = [
                    'action' => 'complete3ds',
                    'site_id' => $this->_site_id,
                    'transaction_id' => $result['transaction_id'],
                    'customer_ip' => $query['customer_ip'],
                    'pa_res' => $result['pa_res'],
                    'comment' => $query['comment'],
                    'force_disable_callback' => 1,
                    'first_callback_delay' => 0
                ];

                $query3DS['signature'] = $this->_getSign($query3DS);

                $timeout = self::$timeout - $request->getInfo()['total_time'];

                if ($timeout <= 0) {
                    $this->_transaction->status = self::STATUS_TIMEOUT;
                    $this->_transaction->save(false);

                    $answer['status'] = 'error';
                    $answer['message'] = self::ERROR_NETWORK;
                    return $answer;
                }

                $request3DS = (new QueryBuilder($this->_getUrl($this->_transaction->currency, $this->_transaction->payment_method)))
                    ->setParams($query3DS)
                    ->asPost()
                    ->setOptions([
                        CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
                        CURLOPT_TIMEOUT => $timeout,
                    ])
                    ->send();
                $result3DS = $request3DS->getResponse(true);

            }

            $answer['redirect']['method'] = 'URL';
            $answer['redirect']['url'] = $params['success_url'];
            $answer['redirect']['params'] = [];

            $answer['data'] = $this->_transaction::getDepositAnswer($this->_transaction);
            $answer['data']['amount'] = $result['amount'] / 100;
            $answer['data']['merchant_refund'] = $result['amount'] / 100;

            $this->_transaction->save(false);
        } else if ($request->getErrno() != CURLE_OK) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;

        } else {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save();

            $answer['status'] = 'error';

            if (isset($result['message'])) {
                $answer['message'] = json_encode($result['message']);
            } else {
                $answer['message'] = 'An error has occurred';
            }

            $message = "Request url = " . static::GATE_URL . 'op/json/';
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-ecommpay-invoice');
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

        if (!$this->_checkIP()) {
            $this->logAndDie(
                'Ecommpay receive() from undefined server',
                'IP = ' . Yii::$app->request->getUserIP(),
                "Undefined server\n"
            );
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        if ($this->_site_id != $receiveData['site_id']) {
            $this->logAndDie(
                'Ecommpay receive() user_id not equal received site_id',
                "site_id = {$this->_site_id}\nreceived site_id = {$receiveData['site_id']}",
                "Transaction user_id not equal received user_id\n"
            );
        }

        $amount = ($receiveData['amount']/100);

        if ($this->_transaction->amount != $amount) {
            $this->logAndDie(
                'Ecommpay receive() transaction amount not equal received amount',
                "Transaction amount = {$this->_transaction->amount}\nreceived amount = {$amount}",
                "Transaction amount not equal received amount\n"
            );
        }

        if (strtolower($data['currency']) !== strtolower($receiveData['currency'])) {
            $this->logAndDie(
                'Ecommpay receive() different currency',
                'Casino currency = ' . $data['currency'] . "\nreceived currency = " . $receiveData['currency'],
                "Different currency\n"
            );
        }

        if (static::_isFinalStatus($receiveData['status_id'])) {
            if (static::_isSuccessStatus($receiveData['status_id'])) {
                $this->_transaction->status = self::STATUS_SUCCESS;
            } else {
                $this->_transaction->status = self::STATUS_ERROR;
            }
            $this->_transaction->save(false);
        } else {
            $this->_transaction->status = self::STATUS_PENDING;
            $this->_transaction->save(false);
        }

        return true;
    }

    /**
     * TODO check it
     * Check if the seller has enough money.
     * Getting required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], 'withdraw');

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        return [
            'data' => [
                'fields' => static::_getFields($params['currency'], $params['payment_method'], 'withdraw'),
                'currency' => $params['currency'],
                'buyer_receive' => null,
                'merchant_write_off' => null,
                'amount' => $params['amount'],
            ]
        ];
    }

    /**
     * TODO this
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params)
    {
        $this->_transaction = $params['transaction'];
        $requests = $params['requests'];

        $validate = static::validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, 'withdraw');

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
                'message' => 'transaction_id is not unique'
            ];
        }

        $requests['m_out']->transaction_id = $this->_transaction->id;
        $requests['m_out']->data           = $requests['merchant'];
        $requests['m_out']->type           = 1;
        $requests['m_out']->save(false);

        $purseAmount = $this->_getBalance($this->_transaction->currency);

        if ($purseAmount <= ($this->_transaction->amount * 100)) {
            //$this->_transaction->delete();
            return [
                'status' => 'error',
                'message' => "Insufficient funds"
            ];
        }

        $query = [
            'action' => 'create_invoice',
            'site_id' => $this->_site_id,
            'external_id' => $this->_transaction->id,
            'amount' => $this->_transaction->amount * 100,
            'currency' => $this->_transaction->currency,
            'customer_ip' => Yii::$app->request->getUserIP(),
            'comment' => $this->_transaction->comment,
            'force_disable_callback' => 1,
            'first_callback_delay' => 0
        ];

        $hidden = static::_getHiddenFields($this->_transaction->currency, $this->_transaction->payment_method, 'deposit', $params);

        $requisites = json_decode($this->_transaction->requisites, true);

        if (!empty($requisites)) {
            $query = array_merge($query, $requisites, $hidden);
        } else {
            $query = array_merge($query, $hidden);
        }

        $query['signature'] = $this->_getSign($query);

        $this->_transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $this->_transaction->id;
        $requests['out']->data = $this->_transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $request = (new QueryBuilder($this->_getUrl($this->_transaction->currency, $this->_transaction->payment_method)))
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
        $requests['pa_in']->data           = $this->_transaction->result_data;
        $requests['pa_in']->type           = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-ecommpay-withdraw');

        if ($this->_isSuccess($result)) {

            $this->_transaction->external_id = $result['data']['withdraw']['id'];
            $this->_transaction->receive = $result['data']['withdraw']['psAmount'];
            $this->_transaction->amount = $result['data']['withdraw']['payeeReceive'];
            $this->_transaction->status = Payment::STATUS_CREATED;
            $this->_transaction->save(false);

            $answer = [
                'data' => [
                    'transaction_id' => $this->_transaction->merchant_transaction_id,
                    'status' => $this->_transaction->status,
                    'buyer_receive' => $this->_transaction->receive,
                    'amount' => $this->_transaction->amount,
                    'currency' => $this->_transaction->currency,
                ]
            ];

            $this->_transaction->save(false);

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

            $answer = ['status' => 'error'];

            if (isset($result['message'])) {
                $answer['message'] = $result['message'];
            } else {
                $answer['message'] = 'An error has occurred';
            }

            $message = "Request url = " . static::API_URL . 'withdraw';
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-ecommpay-withdraw');
        }

        return $answer;
    }

    /**
     * TODO to check it
     * @param object $transaction
     * @param null $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if (in_array($transaction->status, Payment::getNotFinalStatuses())) {

            $query = [
                'action' => 'order_info',
                'site' => $this->_site_id,
                'external_id' => $transaction->id,
            ];

            if ($transaction->way === 'deposit') {
                $query['type_id'] = 3;
            } elseif ($transaction->way === 'withdraw') {
                $query['type_id'] = 11;
            }

            $query['signature'] = $this->_getSign($query);

            $request = (new QueryBuilder(static::GATE_URL . 'op/json'))
                ->setParams($query)
                ->asPost()
                ->send();

            return $result = $request->getResponse(true);

            if (isset($result['status_id'])) {
                if (static::_isFinalStatus($result['status'])) {
                    if (static::_isSuccessStatus($result['status'])) {
                        $this->_transaction->status = self::STATUS_SUCCESS;
                    } else {
                        $this->_transaction->status = self::STATUS_ERROR;
                    }
                } else {
                    $this->_transaction->status = self::STATUS_PENDING;
                }
            }

            $transaction->save(false);

            if ($model_req) {
                $model_req->transaction_id = $this->_transaction->id;
                $model_req->data = json_encode($result);
                $model_req->type = 8;
                $model_req->save(false);

                Yii::info($result, 'payment-ecommpay');
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $currency
     * @param string $method
     * @return string
     */
    private function _getUrl(string $currency, string $method): string
    {
        $currencies = static::getSupportedCurrencies();

        return static::GATE_URL . $currencies[$currency][$method]['url'] .'/json/';
    }

    /**
     * @param array $data
     * @return bool
     */
    private function _isSuccess(array $data)
    {
        return isset($data['code']) && ($data['code'] == 0 || $data['code'] == 50);
    }

    /**
     * @return string
     */
    private function _getTime()
    {
        $date = new \DateTime('now');
        $date->modify("+{$this->_lifetime}");

        return $date->format('Y-m-d\TH-i-s');
    }

    /**
     * @param array $data
     * @return bool
     */
    private function _checkReceivedSign(array $data): bool
    {
        $sign = $data['signature'];
        unset($data['signature']);
        $expectedSign = $this->_getSign($data);

        if ($sign != $expectedSign) {
            Yii::error("Ecommpay receive() wrong signature is received: expectedSignature = {$expectedSign} \nSignature = {$sign}", 'payment-ecommpay');
        } else {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function _checkIP(): bool
    {
        return in_array($_SERVER['REMOTE_ADDR'], $this->_allow_ip);
    }

    /**
     * Sign function
     * @param array $data
     * @return string
     */
    private function _getSign(array $data): string
    {
        $signArr = [];

        ksort($data, SORT_STRING);

        foreach ($data as $key => $value) {
            if (empty($value)) continue;

            if (is_array($value)) {

                ksort($value, SORT_STRING);
                $arr = [];

                foreach ($value as $k => $v) {
                    $arr[] = "{$k}:{$v}";
                }

                $signArr[] = $key . ":" . implode(';', $arr);
            } else {
                $signArr[] = "{$key}:{$value}";
            }

        }

        array_push($signArr, $this->_salt);

        $signString = implode(';', $signArr);

        return sha1($signString, true);
    }

    /**
     * @param string $currency
     * @return int
     */
    private function _getBalance(string $currency): int
    {
        $query = [
            'action' => 'get_local_group_balance',
            'site_id' => $this->_site_id
        ];

        $query['signature'] = static::_getSign($query);

        $request = (new QueryBuilder(static::GATE_URL . 'op/json/'))
            ->setParams($query)
            ->asPost()
            ->setOptions([
                CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
                CURLOPT_TIMEOUT => self::$timeout,
            ])
            ->send();

        $result = $request->getResponse(true);

        if (isset($result['message']) && $result['message'] === 'Success') {
            $balance = json_decode($result['message'], true);
            return $balance[$currency] ?? 0;
        }

        return 0;
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

        if (isset($method['min']) && $method['min'] > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
        if (isset($method['max']) && $method['max'] <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;

        if (isset($method['min']) && isset($method['max'])) {
            if ($method['min'] > $amount || $method['max'] <= $amount) {
                return "Amount should to be more than '{$method['min']}' and less than '{$method['max']}'";
            }
        }

        if (isset($method['min'])) {
            if ($method['min'] > $amount) {
                return "Amount should to be more than '{$method['min']}'";
            }
        }

        return true;
    }

    /**
     * Getting query for search transaction after success or fail transaction
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['external_id']) ? ['id' => $data['external_id']] : [];
    }

    /**
     * Getting transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['external_id'] ?? 0;
    }

    /**
     * Getting success answer for stopping send callback data
     * @return mixed
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
     * Checking final status for deposit
     * @param int $status
     * @return boolean
     */
    private static function _isFinalStatus(int $status): bool
    {
        $statuses = [
            '2' => false, // Транзакция в текущий момент ждет оповещения от внешней системы
            '3' => false, // Транзакция в текущий момент ожидает действий от мерчанта - confirm или void
            '4' => true, // Транзакция была проведена с положительным результатом
            '5' => false, // Отмененная авторизация
            '6' => true, // Отказ процессора от проведения транзакции
            '7' => true, // Система Fraud Stop отклонила операцию
            '8' => true, // По результатам работы MPI было принято решение не проводить транзакцию
            '10' => false, // Произошла ошибка во время проведения платежа
            '11' => true, // unsupported protocol operation
            '12' => true, // protocol configuration error
            '13' => false, // Счет, выставленный к оплате, просрочен
            '14' => false, // Транзакция отменена пользователем
            '15' => false, // Отказ при обработке транзакции внутри Accentpay
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking success status for deposit
     * @param int $status
     * @return bool
     */
    private static function _isSuccessStatus(int $status): bool
    {
        return $status == 4;
    }

    /**
     * @param string $currency
     * @param string $method
     * @param string $type
     * @return array
     */
    private static function _getFields(string $currency, string $method, string $type): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$method]['fields'][$type] ?? [];
    }

    /**
     * @param string $currency
     * @param string $method
     * @param string $type
     * @param array $params
     * @return array
     */
    private function _getHiddenFields(string $currency, string $method, string $type, array $params): array
    {
        $currencies = static::getSupportedCurrencies();

        $fields = [];

        if ($type === 'deposit') {
            $fields = $currencies[$currency][$method]['fields']['d_hidden'] ?? [];
        } elseif ($type === 'withdraw') {
            $fields = $currencies[$currency][$method]['fields']['w_hidden'] ?? [];
        }

        foreach ($fields as $key => $field) {
            if ($field === null) {
                switch ($field) {
                    case 'lifetime':
                        $fields[$key] = static::_getTime();
                        break;
                    case 'term_url':
                    case 'ok_url':
                    case 'redirection_url':
                        $fields[$key] = $params['success_url'];
                        break;
                    case 'nok_url':
                        $fields[$key] = $params['fail_url'];
                        break;
                    case 'email_subject':
                        $fields[$key] = $this->_transaction->comment;
                        break;
                    case 'site':
                        $fields[$key] = $this->_site_id;
                        break;
                }
            }
        }

        return $fields;
    }
}