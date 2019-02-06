<?php

namespace pps\nixmoney;

use api\classes\ApiError;
use \pps\payment\Payment;
use Yii;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class NixMoney
 * @package pps\nixmoney
 */
class NixMoney extends Payment
{
    const API_URL = 'https://www.nixmoney.com/';             // для реального сервера
    const SCI_URL = 'https://www.nixmoney.com/merchant.jsp'; // для реального сервера
//	const API_URL = 'http://dev.nixmoney.com/';             // для тестов
//	const SCI_URL = 'http://dev.nixmoney.com/merchant.jsp'; // для тестов

    /**
     * @var object
     */
    private $_transaction;
    /**
     * @var object
     */
    private $_requests;
    /**
     * @var object
     */
    private $_receive;
    /**
     * User login
     * @var string
     */
    private $_account_id;
    /**
     * User password
     * @var string
     */
    private $_pass_phrase;
    /**
     * Number of your account
     * @var string
     */
    private $_account;
    /** @var int */
    private $_query_errno;

    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (!$data['account_id']) {
            throw new InvalidParamException('account_id empty');
        }
        if (!$data['pass_phrase']) {
            throw new InvalidParamException('pass_phrase empty');
        }
        if (!$data['account']) {
            throw new InvalidParamException('account empty');
        }

        $this->_account_id = $data['account_id'];
        $this->_pass_phrase = $data['pass_phrase'];
        $this->_account = $data['account'];
    }

    /**
     * Preliminary calculation of the invoice.
     * Get required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_DEPOSIT);

        if (!$validate) {
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
                'fields' => static::getFields($params['currency'], $params['payment_method'], self::WAY_DEPOSIT),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_refund' => null,
                'commission' => static::getCommission($params['currency'], $params['amount'], self::WAY_DEPOSIT, $params['commission_payer']),
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
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];

        $validate = static::validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, self::WAY_DEPOSIT);

        if (!$validate) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        try {
            $this->_transaction->commission = static::getCommission($this->_transaction->currency, $this->_transaction->amount, self::WAY_DEPOSIT, $this->_transaction->commission_payer);
            $this->_transaction->save(false);
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

        $data = json_decode($this->_requests['merchant']);

        $this->_requests['m_out']->transaction_id = $this->_transaction->id;
        $this->_requests['m_out']->data = $this->_requests['merchant'];
        $this->_requests['m_out']->type = 1;
        $this->_requests['m_out']->save(false);

        if (!static::checkCurrencyWallet($this->_account, $this->_transaction->currency)) {
            return [
                'status' => 'error',
                'message' => 'NixMoney withdraw. Currency and payee account do not match'
            ];
        }

        $redirectParams = [
            'PAYEE_ACCOUNT' => $this->_account,
            'PAYEE_NAME' => $data->desc ?? self::WAY_DEPOSIT,
            'PAYMENT_AMOUNT' => $this->_transaction->amount,
            'PAYMENT_URL' => $params['success_url'] . '?PAYMENT_ID=' . $this->_transaction->id,
            'NOPAYMENT_URL' => $params['fail_url'] . '?PAYMENT_ID=' . $this->_transaction->id,
            'BAGGAGE_FIELDS' => 'PAYMENT_ID',
            'PAYMENT_ID' => $this->_transaction->id,
            'STATUS_URL' => $params['callback_url'],
            'SUGGESTED_MEMO' => self::WAY_DEPOSIT,
//          'PAYER_EMAIL'    => '', // (опционально) Производить оплату только с кошельков зарегистрированных на данный email-адрес (при авторизации у пользователя не будет возможности изменить выбранный email-адрес).
        ];

        $this->_transaction->query_data = json_encode($redirectParams);

        $this->_requests['out']->transaction_id = $this->_transaction->id;
        $this->_requests['out']->data = $this->_transaction->query_data;
        $this->_requests['out']->type = 2;
        $this->_requests['out']->save(false);

        Yii::info(['query' => $redirectParams, 'result' => []], 'payment-cryptonator-invoice');

        $this->_transaction->result_data = json_encode([]);

        $this->_requests['pa_in']->transaction_id = $this->_transaction->id;
        $this->_requests['pa_in']->data = $this->_transaction->result_data;
        $this->_requests['pa_in']->type = 3;
        $this->_requests['pa_in']->save(false);

        $this->_transaction->refund = $this->_transaction->amount;
        $this->_transaction->status = self::STATUS_CREATED;
        $this->_transaction->save(false);

        return [
            'redirect' => [
                'method' => 'POST',
                'url' => static::SCI_URL,
                'params' => $redirectParams,
            ],
            'data' => $this->_transaction::getDepositAnswer($this->_transaction)
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
        $this->_receive = $data['receive_data'];

        if (in_array($this->_transaction->status, self::getFinalStatuses())) {
            // check if transaction was executed
            return true;
        }
        if (!isset($this->_receive['V2_HASH'])) {
            Yii::error("NixMoney receive() signature is not set: Transaction received signature is not set!", 'payment-nixmoney-receive');

            return false;
        }
        if (!$this->checkReceivedSign($this->_receive)) {
            return false;
        }
        if (floatval($this->_transaction->amount) != floatval($this->_receive['PAYMENT_AMOUNT'])) {
            // If amounts not equal
            Yii::error("NixMoney receive() transaction amount not equal received amount: Transaction amount = {$this->_transaction->refund}\nreceived amount = {$this->_receive['PAYMENT_AMOUNT']}", 'payment-nixmoney-receive');

            return false;
        }
        if ($data['currency'] != $this->_receive['PAYMENT_UNITS']) {
            // If different currency
            Yii::error("NixMoney receive() different currency: Merchant currency = {$data['currency']}\nreceived currency = {$this->_receive['PAYMENT_UNITS']}", 'payment-nixmoney-receive');

            return false;
        }

        $this->_transaction->status = self::STATUS_SUCCESS;

        if (intval($this->_receive['PAYMENT_BATCH_NUM']) > 0) {
            $this->_transaction->external_id = $this->_receive['PAYMENT_BATCH_NUM'];
        }

        $this->_transaction->save(false);

        return true;
    }

    /**
     * Check if the seller has enough money.
     * Get required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_WITHDRAW);

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

        $answer = [
            'data' => [
                'fields' => static::getFields($params['currency'], $params['payment_method'], self::WAY_WITHDRAW),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'buyer_receive' => null,
                'commission' => static::getCommission($params['currency'], $params['amount'], self::WAY_WITHDRAW, $params['commission_payer']),
                'merchant_write_off' => null,
            ],
        ];

        return $answer;
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params)
    {
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];

        $requisites = json_decode($params['transaction']['requisites'], true);

        $validate = static::validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, self::WAY_WITHDRAW);

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

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $this->_requests['m_out']->transaction_id = $this->_transaction->id;
        $this->_requests['m_out']->data = $this->_requests['merchant'];
        $this->_requests['m_out']->type = 1;
        $this->_requests['m_out']->save(false);

        if (!static::checkCurrencyWallet($this->_account, $this->_transaction->currency)) {
            return [
                'status' => 'error',
                'message' => 'NixMoney withdraw. Currency (' . $this->_transaction->currency . ') and payer account (' . $this->_account . ') do not match'
            ];
        }
        if (!static::checkCurrencyWallet($requisites['account'], $this->_transaction->currency)) {
            return [
                'status' => 'error',
                'message' => 'NixMoney withdraw. Currency (' . $this->_transaction->currency . ') and payee account (' . $requisites['account'] . ') do not match'
            ];
        }

        $query = [
            'PASSPHRASE' => $this->_pass_phrase,                                    // Ваш пароль для доступа в личный кабинет.
            'PAYER_ACCOUNT' => $this->_account,                                        // Номер Вашего счета-отправителя, с которого будут списаны средства.
            'PAYEE_ACCOUNT' => $requisites['account'],                                 // Номер счета-получателя, на который будут приниматься средства.
            'AMOUNT' => number_format($this->_transaction->amount, 8, ".", ""), // Сумма к переводу (XXXX.XX)
            'MEMO' => self::WAY_WITHDRAW . ' payment',                     // Комментарии к переводу
        ];

        $this->_transaction->query_data = json_encode($query);
        $this->_transaction->save(false);

        $this->_requests['out']->transaction_id = $this->_transaction->id;
        $this->_requests['out']->data = $this->_transaction->query_data;
        $this->_requests['out']->type = 2;
        $this->_requests['out']->save(false);

        $result = $this->query(static::API_URL . 'send', $query);

        $this->_transaction->result_data = json_encode($result);
        $this->_transaction->save(false);

        $this->_requests['pa_in']->transaction_id = $this->_transaction->id;
        $this->_requests['pa_in']->data = $this->_transaction->result_data;
        $this->_requests['pa_in']->type = 3;
        $this->_requests['pa_in']->save(false);

        \Yii::info(
            [
                'query' => $query,
                'result' => $result
            ],
            'payment-nixmoney-withdraw'
        );

        $answer = [];

        if (!isset($result['ERROR'])) {
            if (!empty($result['PAYMENT_BATCH_NUM']) && (floatval($result['PAYMENT_BATCH_NUM']) > 0)) {
                $this->_transaction->external_id = $result['PAYMENT_BATCH_NUM'];
                $this->_transaction->receive = $result['PAYMENT_AMOUNT'];
                $this->_transaction->status = self::STATUS_SUCCESS;
                $this->_transaction->save(false);

                $answer = [
                    'data' => $this->_transaction::getWithdrawAnswer($this->_transaction)
                ];
            }
        } elseif ($this->_query_errno == CURLE_OPERATION_TIMEOUTED) {
            $this->_transaction->status = self::STATUS_TIMEOUT;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } elseif ($this->_query_errno != CURLE_OK) {
            $this->_transaction->status = self::STATUS_NETWORK_ERROR;
            $this->_transaction->result_data = $this->_query_errno;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);
        }

        if (isset($result['ERROR']) || !isset($result['PAYMENT_BATCH_NUM'])) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save();

            $answer = [
                'status' => 'error',
                'message' => self::ERROR_OCCURRED,
            ];

            if (isset($result['ERROR'])) {
                $answer['message'] = $result['ERROR'];
            }

            $message = "Request url = " . static::API_URL . 'send';
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-nixmoney-withdraw');
        }

        return $answer;
    }

    /**
     * Get commission
     * @param string $currency
     * @param float $amount
     * @param string $way
     * @param $payer
     * @return float|string
     */
    private static function getCommission(string $currency, float $amount, string $way, $payer)
    {
        $returns = 0;
        $sum_percent = 0;

        $currencies = static::getSupportedCurrencies();
        if (!empty($currencies[$currency]['nixmoney']['commission'][$way])) {
            $commissions = $currencies[$currency]['nixmoney']['commission'][$way];

            // Враховуємо відсоткову частину комісії
            if (floatval($commissions['percent']) > 0) {
                $sum_percent = 100;
                if ($payer == self::COMMISSION_MERCHANT) {
                    $sum_percent = (100 - $commissions['percent']);
                }

                $returns += $amount * $commissions['percent'] / $sum_percent;
            }

            return round(floatval($returns), 8);
        }

        return 'unknown';
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
            $query = [
                'ACCOUNTID' => $this->_account_id,
                'PASSPHRASE' => $this->_pass_phrase,
                'STARTMONTH' => intval(date('m', $transaction->created_at)),
                'STARTDAY' => intval(date('d', $transaction->created_at)),
                'STARTYEAR' => intval(date('Y', $transaction->created_at)),
                'ENDMONTH' => intval(date('m', $transaction->created_at)),
                'ENDDAY' => intval(date('d', $transaction->created_at)),
                'ENDYEAR' => intval(date('Y', $transaction->created_at)),
//                'PAYMENT_ID' => $transaction->external_id,
            ];

            $response = $this->query(static::API_URL . '/history', $query);

            \Yii::info(
                [
                    'query' => $query,
                    'result' => $response
                ],
                'payment-nixmoney-getstatus'
            );

            if (isset($response)) {
                if (strpos($response, 'No Records Found') === false) {
                    if (static::_parseStatusResponse($response, $transaction->external_id)) {
                        $transaction->status = self::STATUS_SUCCESS;
                    } else {
                        $transaction->status = self::STATUS_PENDING;
                    }

                    $transaction->save(false);
                }
            }

            if ($model_req) {
                $model_req->transaction_id = $transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            return true;
        }

        return false;
    }

    /**
     * Main query method
     * @param string $url
     * @param array $params
     * @param bool $post
     * @return boolean|object|array
     */
    private function query(string $url, array $params, $post = true)
    {
        if (!$post && count($params) > 0) {
            $url .= "?" . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, $post);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);

        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response !== false) {
            $response_arr = [];
            $result = [];

            if (preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $response, $response_arr, PREG_SET_ORDER)) {

                foreach ($response_arr AS $value) {
                    $result[$value[1]] = $value[2];
                }

                return $result;
            }
        }

        return $response;
    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet, string $separate = ":"): string
    {
        return strtoupper(md5(
            $dataSet['PAYMENT_ID'] . $separate .
            $dataSet['PAYEE_ACCOUNT'] . $separate .
            $dataSet['PAYMENT_AMOUNT'] . $separate .
            $dataSet['PAYMENT_UNITS'] . $separate .
            $dataSet['PAYMENT_BATCH_NUM'] . $separate .
            $dataSet['PAYER_ACCOUNT'] . $separate .
            strtoupper(md5($this->_pass_phrase)) . $separate .
            $dataSet['TIMESTAMPGMT']
        ));
    }

    /**
     * Parse response updateStatus function
     * @param string $dataSet
     * @param string $external_id
     * @return bool
     */
    private function _parseStatusResponse(string $dataSet, string $external_id): bool
    {
        $arrays = explode("\n", $dataSet);
        $keys = explode(",", $arrays[0]);

        foreach ($keys as $key => $value) {
            $keys[$key] = str_replace(" ", "_", $value);
        }

        for ($i = 1; $i < sizeof($arrays); $i++) {
            $values = explode(",", $arrays[$i]);

            foreach ($keys as $key => $value) {
                if (strpos($values[$key], $external_id) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checking success status for deposit
     * @param int $status
     * @return bool
     */
    public function isSuccess(int $status): bool
    {
        return $status == self::STATUS_SUCCESS;
    }

    /**
     * @param array $params
     * @return bool
     */
    private function checkReceivedSign(array $params): bool
    {
        $expectedSign = $this->_getSign($params);

        if ($params['V2_HASH'] !== $expectedSign) {
            Yii::error("NixMoney receive() wrong sign is received: expectedSign = {$expectedSign} \nSign = {$params['V2_HASH']}", 'payment-nixmoney');

            return false;
        }

        return true;
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
        if (!isset($currencies[$currency][$method][$way]) || !$currencies[$currency][$method][$way]) {
            return "Payment system does not support '{$way}'";
        }

        $method = $currencies[$currency][$method];

        if (0 > $amount) {
            if (isset($method['min']) && $method['min'] > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            return "Amount should to be more than '0'";
        }

        return true;
    }

    /**
     * Get query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['PAYMENT_ID']) ? ['id' => $data['PAYMENT_ID']] : [];
    }

    /**
     * Get transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['PAYMENT_ID'] ?? 0;
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
     * Get fields for filling
     * @param string $currency
     * @param string $payment_method
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $payment_method, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency][$payment_method]['fields'][$way] ?? [];
    }

    /**
     * Get currencies list
     * @return array
     */
    public static function getCurrenciesList(): array
    {
        $returns = [];
        $currencies = static::getSupportedCurrencies(false);

        foreach ($currencies as $currency => $arrays) {
            $returns[] = $currency;
        }

        return $returns;
    }

    /**
     * @param bool $getListCurr
     * @return array
     */
    public static function getApiFields(bool $getListCurr = true): array
    {
        $currencyList = ($getListCurr) ? static::getCurrenciesList() : [];

        return [
            'nixmoney' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'account' => [
                        'required' => true,
                        'label' => 'Номер кошелька клиента',
                        'regex' => '^[U|E|B|L|C|F|P|X|D]{1}[0-9]{14}$',
                        'currencies' => $currencyList,
                    ]
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getCommissionsList(): array
    {
        return [
            'nixmoney' => [
                'commission' => [
                    self::WAY_DEPOSIT => [
                        'percent' => 0,
                        'min' => [
                            'sum' => 0,
                            'currency' => '',
                        ],
                        'max' => [
                            'sum' => 0,
                            'currency' => '',
                        ],
                        'value' => 'percent',
                    ],
                    self::WAY_WITHDRAW => [
                        'percent' => 0.5,
                        'min' => [
                            'sum' => 0.05,
                            'currency' => 'EUR',
                        ],
                        'max' => [
                            'sum' => 0,
                            'currency' => '',
                        ],
                        'value' => 'percent',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return bool
     */
    public static function checkCurrencyWallet(string $wallet, string $currency): bool
    {
        $CurrencyWallet = [
            'USD' => 'U',
            'EUR' => 'E',
            'BTC' => 'B',
            'LTC' => 'L',
            'CRT' => 'C',
            'FTC' => 'F',
            'PPC' => 'P',
            'DOGE' => 'D',
            'CLR' => 'K',
            'XBL' => 'X',
            'SVC' => 'V',
            'MVR' => 'M',
        ];

        return (strpos(strtoupper($CurrencyWallet[$currency]), strtoupper(substr($wallet, 0, 1))) !== false);
    }

    /**
     * Get supported currencies
     * @return array
     */
    public static function getSupportedCurrencies(bool $getListCurr = true, $ps = 'nixmoney', $ps_name = 'NixMoney'): array
    {
        $ApiFields = static::getApiFields($getListCurr);
        $commissionsList = static::getCommissionsList();

        $fields = $ApiFields[$ps];
        $commissions = $commissionsList[$ps];

        return [
            'USD' => [
                $ps => [
                    'code' => $ps_name . ' US dollar',
                    'name' => 'US dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'EUR' => [
                $ps => [
                    'code' => $ps_name . ' Euro',
                    'name' => 'Euro',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BTC' => [
                $ps => [
                    'code' => $ps_name . ' Bitcoin (BTC)',
                    'name' => 'Bitcoin (BTC)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'LTC' => [
                $ps => [
                    'code' => $ps_name . ' Litecoin (LTC)',
                    'name' => 'Litecoin (LTC)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CRT' => [
                $ps => [
                    'code' => $ps_name . ' CryptoGraphic Coin (CRT)',
                    'name' => 'CryptoGraphic Coin (CRT)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'FTC' => [
                $ps => [
                    'code' => $ps_name . ' Feathercoin (FTC)',
                    'name' => 'Feathercoin (FTC)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PPC' => [
                $ps => [
                    'code' => $ps_name . ' PPcoin (PPC)',
                    'name' => 'PPcoin (PPC)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'DOGE' => [
                $ps => [
                    'code' => $ps_name . ' Dogecoin (DOGE)',
                    'name' => 'Dogecoin (DOGE)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CLR' => [
                $ps => [
                    'code' => $ps_name . ' CopperLark (CLR)',
                    'name' => 'CopperLark (CLR)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            /*            'XBL' => [
                            $ps => [
                                'code' => $ps_name . ' (XBL)',
                                'name' => 'XBL',
                                'fields' => $fields,
                                'commission' => $commissions,
                                self::WAY_DEPOSIT => true,
                                self::WAY_WITHDRAW => true,
                            ],
                        ],*/
            'SVC' => [
                $ps => [
                    'code' => $ps_name . ' Silver coin (SVC)',
                    'name' => 'Silver coin (SVC)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MVR' => [
                $ps => [
                    'code' => $ps_name . ' MAVRO coin (MVR)',
                    'name' => 'MAVRO coin (MVR)',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
        ];
    }
}