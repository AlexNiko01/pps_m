<?php

namespace pps\skrill;

use api\classes\ApiError;
use \pps\payment\Payment;
use Yii;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class Skrill
 * @package pps\skrill
 */
class Skrill extends Payment
{
    const API_URL = 'https://www.skrill.com/app/pay.pl';
    const SCI_URL = 'https://pay.skrill.com';
    const MQI_URL = 'https://www.moneybookers.com/app/query.pl';

    // Надсилається, коли транзакція оброблена, і кошти були перераховані на ваш
    // рахунок Skrill
    const STATUS_SKRYL_PROCESSED = 2;

    // Надсилається, коли клієнти платять через автономний банківський переказ.
    // Такі операції будуть автоматично оброблятися, якщо банківський переказ
    // отриманий компанією Skrill.
    // !!! Примітка !!!
    // Ми настійно рекомендуємо не обробляти замовлення чи транзакції у вашій
    // системі після отримання цього статусу від Skrill.
    const STATUS_SKRYL_PENDING = 0;

    // Операції, що очікують, можуть бути скасовані вручну відправником в їхній
    // онлайн-історії облікового запису Skrill Digital Wallet або вони
    // автоматично скасовуватимуться через 14 днів, якщо вони все ще очікують на
    // розгляд
    const STATUS_SKRYL_CANCELLED = -1;

    // Цей статус, як правило, надсилається, коли клієнт намагається здійснити
    // оплату за допомогою кредитної картки або прямого дебету, але постачальник
    // Skrill відхиляє транзакцію.
    // Його також можна відправити, якщо транзакція відхилена внутрішнім
    // шахрайським двигуном Skrill, наприклад:
    // failed_reason_code 54 - не вдалося через обмеження внутрішньої безпеки.
    const STATUS_SKRYL_FAILED = -2;

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
     * @var integer
     */
    private $_merchant_id;
    /**
     * @var string
     */
    private $_email;
    /**
     * @var string
     */
    private $_password;
    /**
     * @var string
     */
    private $_secret_word;
    /**
     * @var array
     */
    private $_allow_ip;

    /**
     * work with XML
     */
    // raw xml
    private $rawXML;
    // array returned by the xml parser
    private $valueArray = [];
    private $keyArray = [];

    // arrays for dealing with duplicate keys
    private $duplicateKeys = [];

    // return data
    private $output = [];
    private $status;
    /**
     * Information about the last transfer retrieved from curl_getinfo
     * function.
     *
     * @var    array
     * @access protected
     */
    protected $info = [];
    protected $response_body;
    protected $response_headers;
    /** @var int */
    private $_query_errno;

    /**
     * Fill the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (!$data['merchant_id']) {
            throw new InvalidParamException('merchant_id empty');
        }
        if (!$data['email']) {
            throw new InvalidParamException('email empty');
        }
        if (!$data['password']) {
            throw new InvalidParamException('password empty');
        }
        if (!$data['secret_word']) {
            throw new InvalidParamException('secret_word empty');
        }

        $this->_merchant_id = $data['merchant_id'];
        $this->_email = $data['email'];
        $this->_password = $data['password'];
        $this->_secret_word = $data['secret_word'];

        // The full list of Skrill IP ranges the receipt of status response are:
        // 91.208.28.0/24, 93.191.174.0/24, 193.105.47.0/24, 195.69.173.0/24
        $ip_lists = [
            '91.208.28.',
            '93.191.174.',
            '193.105.47.',
            '195.69.173.',
        ];

        foreach ($ip_lists as $ip) {
            for ($i = 0; $i < 256; $i++) {
                $this->_allow_ip[] = $ip . $i;
            }
        }

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
        $validate = static::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_DEPOSIT);

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
                'merchant_refund' => null,
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

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
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

        $this->_requests['m_out']->transaction_id = $this->_transaction->id;
        $this->_requests['m_out']->data = $this->_requests['merchant'];
        $this->_requests['m_out']->type = 1;
        $this->_requests['m_out']->save(false);

        $data = json_decode($this->_requests['merchant'], true);

        $redirectParams = [
            'pay_to_email' => $this->_email,
            'transaction_id' => $this->_transaction->id,
            'return_url' => $params['success_url'],
            'cancel_url' => $params['fail_url'],
            'status_url' => $params['callback_url'],
//            'language'            => '',
            'merchant_fields' => 'tid,merchant',
            'tid' => $this->_transaction->id,
            'merchant' => $this->_requests['merchant'],
            'amount' => number_format($this->_transaction->amount, 2),
            'currency' => $this->_transaction->currency,
//            'firstname'           => '',
//            'lastname'            => '',
//            'address'             => '',
//            'postal_code'         => '',
//            'city'                => '',
//            'country'             => '',
        ];

        if (isset($data['pay_from_email'])) {
            $redirectParams['pay_from_email'] = $data['pay_from_email'];
        }

        $this->_transaction->query_data = json_encode($redirectParams);

        $this->_requests['out']->transaction_id = $this->_transaction->id;
        $this->_requests['out']->data = $this->_transaction->query_data;
        $this->_requests['out']->type = 2;
        $this->_requests['out']->save(false);

        Yii::info(['query' => $redirectParams, 'result' => []], 'payment-skrill-invoice');

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
            'data' => $this->_transaction::getDepositAnswer($this->_transaction),
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

        if (!$this->_checkIP()) {
            $this->logAndDie(
                'Skrill receive() from undefined server',
                'IP = ' . Yii::$app->request->getUserIP(),
                "Undefined server"
            );
        }
        if (in_array($this->_transaction->status, self::getFinalStatuses())) {
            // check if transaction was executed
            return true;
        }
        if (!isset($this->_receive['md5sig'])) {
            // If md5sig not set
            $this->logAndDie(
                'Skrill receive() signature is not set',
                "Transaction received signature is not set!",
                "Transaction signature is not set"
            );
        }
        if (!isset($this->_receive['mb_transaction_id'])) {
            // If mb_transaction_id not set
            $this->logAndDie(
                'Skrill receive() mb_transaction_id is not set',
                "Transaction mb_transaction_id is not set!",
                "Transaction mb_transaction_id is not set"
            );
        }
        if (!isset($this->_receive['status'])) {
            // If status not set
            $this->logAndDie(
                'Skrill receive() status is not set',
                "Transaction status is not set!",
                "Transaction status is not set"
            );
        }
        if ($this->_receive['merchant_id'] != $this->_merchant_id) {
            // If merchant_id not equal
            $this->logAndDie(
                'Skrill receive() merchant_id is not equal',
                'Transaction merchant_id = ' . $this->_merchant_id . "\nreceived merchant_id = " . $this->_receive['merchant_id'],
                "Transaction merchant_id is not equal"
            );
        }
        if (!$this->checkReceivedSign($this->_receive)) {
            return false;
        }

        if (isset($this->_receive['status'])) {
            if ($this->_receive['status'] == static::STATUS_SKRYL_FAILED) {
                $this->_transaction->status = self::STATUS_ERROR;
                $this->_transaction->save(false);

                return true;
            } elseif (intval($this->_receive['status']) == static::STATUS_SKRYL_CANCELLED) {
                $this->_transaction->status = self::STATUS_SKRYL_CANCELLED;
                $this->_transaction->save(false);

                return true;
            } elseif (intval($this->_receive['status']) == static::STATUS_SKRYL_PENDING) {
                $this->_transaction->status = self::STATUS_PENDING;
                $this->_transaction->save(false);

                return true;
            } elseif (intval($this->_receive['status']) == static::STATUS_SKRYL_PROCESSED) {
                $this->_transaction->status = self::STATUS_SUCCESS;
                $this->_transaction->save(false);
            }
        }

        if (floatval($this->_transaction->amount) != floatval($this->_receive['amount'])) {
            // If amounts not equal
            $this->logAndDie(
                'Skrill receive() transaction amount not equal received amount',
                'Transaction amount = ' . $this->_transaction->amount . "\nreceived amount = " . $this->_receive['amount'],
                "Transaction amount not equal received amount"
            );
        }

        if (strtoupper($data['currency']) != strtoupper($this->_receive['currency'])) {
            // If different currency
            $this->logAndDie(
                'Skrill receive() different currency',
                'Casino currency = ' . $data['currency'] . "\nreceived currency = " . $this->_receive['currency'],
                "Different currency"
            );
        }

        if (isset($this->_receive['mb_transaction_id'])) {
            $this->_transaction->external_id = $this->_receive['mb_transaction_id'];
            $this->_transaction->save(false);
        }

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
        $validate = self::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_WITHDRAW);

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
                'amount' => number_format($params['amount'], 2),
                'merchant_refund' => null,
                'merchant_write_off' => null,
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
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];
        $merch_params = json_decode($this->_requests['merchant'], true);
        $answer = [];

        $validate = $this->validateTransaction($this->_transaction->currency, $this->_transaction->payment_method, $this->_transaction->amount, self::WAY_WITHDRAW);

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

        $preWithdrawQuery = [
            'email' => $this->_email,
            'password' => $this->_password,
            'action' => 'prepare',
            'amount' => number_format($merch_params['amount'], 2),
            'currency' => $merch_params['currency'],
            'frn_trn_id' => $this->_transaction->id,
            'rec_payment_id' => $this->_transaction->id,
            'bnf_email' => $merch_params['email'],
            'subject' => 'Your order is ready',
            'note' => 'Details are available on our website.',
        ];

        $this->_transaction->query_data = json_encode(['prepare' => $preWithdrawQuery]);

        $preWithdrawResponse = $this->_query($preWithdrawQuery);

        Yii::info([
            'query' => $preWithdrawQuery,
            'result' => $preWithdrawResponse
        ],
            'payment-skrill'
        );

        if (isset($preWithdrawResponse['data']) && isset($preWithdrawResponse['data']['response'])) {
            if (isset($preWithdrawResponse['data']['response']['sid'])) {
                $withdrawQuery = [
                    'action' => 'transfer',
                    'sid' => $preWithdrawResponse['data']['response']['sid'],
                ];

                $this->_transaction->query_data = json_encode([
                    'prepare' => $preWithdrawQuery,
                    'transfer' => $withdrawQuery
                ]);

                $this->_requests['out']->transaction_id = $this->_transaction->id;
                $this->_requests['out']->data = $this->_transaction->query_data;
                $this->_requests['out']->type = 2;
                $this->_requests['out']->save(false);

                $withdrawResponse = $this->_query($withdrawQuery, true);

                $this->_transaction->result_data = json_encode([
                    'prepare' => $preWithdrawResponse,
                    'transfer' => $withdrawResponse
                ]);

                $this->_requests['pa_in']->transaction_id = $this->_transaction->id;
                $this->_requests['pa_in']->data = $this->_transaction->result_data;
                $this->_requests['pa_in']->type = 3;
                $this->_requests['pa_in']->save(false);

                Yii::info([
                    'query' => $withdrawQuery,
                    'result' => $withdrawResponse
                ],
                    'payment-skrill-withdraw'
                );

                if (
                    isset($withdrawResponse['data']['response']) &&
                    isset($withdrawResponse['data']['response']['transaction']) &&
                    isset($withdrawResponse['data']['response']['transaction']['id']) &&
                    isset($withdrawResponse['data']['response']['transaction']['amount']) &&
                    isset($withdrawResponse['data']['response']['transaction']['status'])
                ) {
                    $reso_trans = $withdrawResponse['data']['response']['transaction'];

                    $this->_transaction->external_id = $reso_trans['id'];
                    $this->_transaction->receive = round(floatval($reso_trans['amount']), 2);
                    $this->_transaction->status = ($reso_trans['status'] == 2) ? self::STATUS_CREATED : self::STATUS_ERROR;
                    $this->_transaction->save(false);

                    $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);
                } else {
                    $this->_transaction->status = self::STATUS_ERROR;
                    $this->_transaction->save();

                    $answer = [
                        'status' => 'error',
                        'message' => static::_getErrorMessage($preWithdrawResponse['data']['response']['error']['error_msg']) ?? self::ERROR_OCCURRED . ' (3)',
                    ];

                    $message = "Request url = " . self::API_URL . '/withdraw';
                    $message .= "\nRequest result = " . print_r($withdrawResponse, true);

                    Yii::error($message, 'payment-skrill-withdraw');
                }

            } elseif (isset($preWithdrawResponse['data']['response']['error'])) {
                $answer = [
                    'status' => 'error',
                    'message' => static::_getErrorMessage($preWithdrawResponse['data']['response']['error']['error_msg']) ?? self::ERROR_OCCURRED . ' (2)',
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
        } else {
            $answer = [
                'status' => 'error',
                'message' => self::ERROR_OCCURRED . ' (1)',
            ];
        }

        return $answer;
    }

    /**
     * Get and update transaction status
     * @param object $transaction
     * @param null|object $model_req
     * @return array
     */
    public function getStatus($transaction, $model_req = null): array
    {
        return [
            'status' => 'error',
            'message' => "Method doesn't supported"
        ];
    }

    /**
     * @param array $params
     * @param bool $post
     * @return array
     * @throws Exception
     */
    private function _query(array $params = [], $post = false): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::API_URL . ((!$post) ? ("?" . http_build_query($params)) : ''));
//            curl_setopt($ch, CURLOPT_HEADER, true);
//            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // close connection when it has finished, not pooled for reuse
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        // Do not use cached connection
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Merchant.SDK/PHP');

        if ($post) {
            $headers = [
                'Content‐Type: application/x‐www‐form‐urlencoded',
            ];

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
        }

        $response = curl_exec($ch);
        $this->_query_errno = curl_errno($ch);

        if ($response === false) {
            $ex = new Exception(curl_error($ch), curl_errno($ch));
            curl_close($ch);
            throw $ex;
        }

        // Now check for an HTTP error
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        $this->XMLParser($response);

        return [
            'info' => $curl_info,
            'headers' => substr($response, 0, $curl_info['header_size']),
            'body' => substr($response, -$curl_info['size_download']),
            'data' => $this->getOutputParsing(),
            'response' => $response
        ];
    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        $concatFields = $this->_merchant_id . //$dataSet['merchant_id'] .
            $dataSet['transaction_id'] .
            strtoupper(md5($this->_secret_word)) . //strtoupper(md5($dataSet['secret_word'])) .
            $dataSet['mb_amount'] .
            $dataSet['mb_currency'] .
            $dataSet['status'];

        return strtoupper(md5($concatFields));
    }

    /**
     * @param array $params
     * @return bool
     */
    private function checkReceivedSign(array $params): bool
    {
        $sign = $params['md5sig'];
        unset($params['md5sig']);

        $expectedSign = $this->_getSign($params);

        if ($expectedSign != rawurldecode($sign)) {
            Yii::error("Skrill receive() wrong sign or auth is received: expectedSign = $expectedSign \nSign = $sign", 'payment-skrill');

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
     * Validate transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    public function validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency][$method][$way]) || !$currencies[$currency][$method][$way]) {
            return "Payment system does not support '{$way}'";
        }

        if ($amount <= 0) {
            self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            return "The amount should be greater than zero (amount = '{$amount}')";
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
        return isset($data['mb_transaction_id']) ? ['id' => $data['mb_transaction_id']] : [];
    }

    /**
     * Get transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['mb_transaction_id'] ?? 0;
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
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency]['skrill']['fields'][$way] ?? [];
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
     * Get sum of commission Cryptonators
     * @param string $currency
     * @param float $amount
     * @param string $way
     * @param string $payer
     * @return float
     */
    private static function getCommission(string $currency, float $amount, string $way, string $payer): float
    {
        $returns = 0;
        $sum_percent = 0;

        $SupportedCurrencies = static::getSupportedCurrencies();

        if (!empty($SupportedCurrencies[$currency]['skrill']['commission'][$way])) {
            $commissions = $SupportedCurrencies[$currency]['skrill']['commission'][$way];

            // Враховуємо відсоткову частину комісії
            if (floatval($commissions['percent']) > 0) {
                $sum_percent = 100;
                if ($payer == self::COMMISSION_MERCHANT) {
                    $sum_percent = (100 - $commissions['percent']);
                }

                $returns += $amount * $commissions['percent'] / $sum_percent;
            }

        }

        return round(floatval($returns), 8);
    }

    /**
     * XML parcer function
     * @param string $xml
     * @return boolean
     */
    private function XMLParser(string $xml): bool
    {
        $this->rawXML = $xml;
        $parser = xml_parser_create();

        // Parcing XML
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);   // Dont bother with empty info

        if (!xml_parse_into_struct($parser, $this->rawXML, $this->valueArray, $this->keyArray)) {
            $this->status = 'error: ' . xml_error_string(xml_get_error_code($parser)) . ' at line ' . xml_get_current_line_number($parser);

            return false;
        }

        xml_parser_free($parser);

        // finding duplicates key in the expanded XML
        for ($i = 0; $i < count($this->valueArray); $i++) {
            // duplicate keys are when two complete tags are side by side
            if ($this->valueArray[$i]['type'] == "complete") {
                if (($i + 1) < count($this->valueArray)) {
                    if ($this->valueArray[$i + 1]['tag'] == $this->valueArray[$i]['tag'] && $this->valueArray[$i + 1]['type'] == "complete") {
                        $this->duplicateKeys[$this->valueArray[$i]['tag']] = 0;
                    }
                }
            }

            // also when a close tag is before an open tag and the tags are the same
            if ($this->valueArray[$i]['type'] == "close") {
                if (($i + 1) < count($this->valueArray)) {
                    if (($this->valueArray[$i + 1]['type'] == "open") && ($this->valueArray[$i + 1]['tag'] == $this->valueArray[$i]['tag'])) {
                        $this->duplicateKeys[$this->valueArray[$i]['tag']] = 0;
                    }
                }
            }
        }

        // tmp array used for stacking
        $stack = [];
        $increment = 0;

        foreach ($this->valueArray as $val) {
            if ($val['type'] == "open") {
                //if array key is duplicate then send in increment
                if (array_key_exists($val['tag'], $this->duplicateKeys)) {
                    array_push($stack, $this->duplicateKeys[$val['tag']]);
                    $this->duplicateKeys[$val['tag']]++;
                } else {
                    // else send in tag
                    array_push($stack, $val['tag']);
                }
            } elseif ($val['type'] == "close") {
                array_pop($stack);

                // reset the increment if they tag does not exist in the stack
                if (array_key_exists($val['tag'], $stack)) {
                    $this->duplicateKeys[$val['tag']] = 0;
                }
            } elseif ($val['type'] == "complete") {
                //if array key is duplicate then send in increment
                if (array_key_exists($val['tag'], $this->duplicateKeys)) {
                    array_push($stack, $this->duplicateKeys[$val['tag']]);
                    $this->duplicateKeys[$val['tag']]++;
                } else {
                    // else send in tag
                    array_push($stack, $val['tag']);
                }

                $this->setArrayValue($this->output, $stack, $val['value'] ?? '');
                array_pop($stack);

                if (!empty($val['attributes'])) {
                    if (is_array($val['attributes'])) {
                        foreach ($val['attributes'] as $attribute => $value) {
                            array_push($stack, $val['tag'] . '_' . $attribute);

                            $this->setArrayValue($this->output, $stack, $value);
                            array_pop($stack);
                        }
                    }
                }
            }

            $increment++;
        }

        $this->status = 'success: xml was parsed';

        return true;
    }

    /**
     * The function of entering the result into an array
     * @param $array
     * @param $stack
     * @param $value
     * @return mixed
     */
    private function setArrayValue(&$array, $stack, $value)
    {
        if ($stack) {
            $key = array_shift($stack);
            $this->setArrayValue($array[$key], $stack, $value);

            return $array;
        } else {
            $array = $value;
        }
    }

    /**
     * The function of obtaining the result of the parsing XML
     * @return array
     */
    public function getOutputParsing(): array
    {
        return $this->output;
    }

    /**
     * The function of obtaining the status of XML parsing
     * @return string
     */
    public function getStatusParsing(): string
    {
        return $this->status;
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
     * @param string $message
     * @return string|bool
     */
    private static function _getErrorMessage(string $message): string
    {
        $errors = [
            'SESSION_EXPIRED' => 'Session has expired. Session IDs are only valid for 15 minutes',
            'CUSTOMER_IS_LOCKED' => 'The customer\'s account is locked for outgoing payments',
            'BALANCE_NOT_ENOUGH' => 'The customer\'s account balance is insufficient',
            'RECIPIENT_LIMIT_EXCEEDED' => 'The customer\'s account limits are not sufficient',
            'CARD_FAILED' => 'The customer\'s credit or debit card failed',
            'REQUEST_FAILED' => 'Generic response for transaction failing for any other reason',
            'ONDEMAND_CANCELLED' => 'The customer has cancelled this Skrill 1-Tap payment',
            'ONDEMAND_INVALID' => 'The Skrill 1-Tap payment requested does not exist1',
            'MAX_REQ_REACHED' => 'Too many failed Skrill 1-Tap payment requests to the API. For security reasons, only two failed attempts per user per 24 hours are allowed',
            'MAX_AMOUNT_REACHED' => 'The payment amount is greater than the maximum amount configured when 1-Tap payments were setup for this user.',
            'INVALID_OR_MISSING_ACTION' => 'Wrong action or no action is provided',
            'LOGIN_INVALID' => 'Email address and/or password were not provided',
            'INVALID_REC_PAYMENT_ID' => 'Invalid recurring payment ID is submitted by the merchant',
            'MISSING_EMAIL' => 'Provide registered email address of merchant account',
            'MISSING_PASSWORD' => 'Provide correct API/MQI password',
            'MISSING_AMOUNT' => 'Provide amount you wish to send',
            'MISSING_CURRENCY' => 'Provide currency you wish to send',
            'MISSING_BNF_EMAIL' => 'Provide email address of the beneficiary',
            'MISSING_SUBJECT' => 'Provide subject of the payment',
            'MISSING_NOTE' => 'Provide notes for the payment',
        ];

        return $errors[$message] ?? false;
    }

    /**
     * @param bool $getListCurr
     * @return array
     */
    public static function getApiFields(bool $getListCurr = true): array
    {
        $currencyList = ($getListCurr) ? static::getCurrenciesList() : [];

        return [
            'skrill' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'email' => [
                        'required' => true,
                        'label' => 'Recipient’s (beneficiary’s) email address',
                        'regex' => '^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$',
                        'currencies' => $currencyList,
                    ],
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getCommissionsList(): array
    {
        return [
            'skrill' => [
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
                        'percent' => 1.5,
                        'min' => [
                            'sum' => 0,
                            'currency' => '',
                        ],
                        'max' => [
                            'sum' => 20,
                            'currency' => 'EUR',
                        ],
                        'value' => 'percent',
                    ],
                ]
            ],
        ];
    }

    /**
     * Get supported currencies
     * @param bool $getListCurr
     * @param string $ps
     * @return array
     */
    public static function getSupportedCurrencies(bool $getListCurr = true, $ps = 'skrill'): array
    {
        $ApiFields = static::getApiFields($getListCurr);
        $commissionsList = static::getCommissionsList();

        $fields = $ApiFields[$ps];
        $commissions = $commissionsList[$ps];

        return [
            'EUR' => [
                $ps => [
                    'name' => 'Euro',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'TWD' => [
                $ps => [
                    'name' => 'Taiwan Dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'USD' => [
                $ps => [
                    'name' => 'U.S. Dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'THB' => [
                $ps => [
                    'name' => 'Thailand Baht',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'GBP' => [
                $ps => [
                    'name' => 'British Pound',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CZK' => [
                $ps => [
                    'name' => 'Czech Koruna',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'HKD' => [
                $ps => [
                    'name' => 'Hong Kong Dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'HUF' => [
                $ps => [
                    'name' => 'Hungarian Forint',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'SGD' => [
                $ps => [
                    'name' => 'Singapore Dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BGN' => [
                $ps => [
                    'name' => 'Bulgarian Leva',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'JPY' => [
                $ps => [
                    'name' => 'Japanese Yen',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PLN' => [
                $ps => [
                    'name' => 'Polish Zloty',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CAD' => [
                $ps => [
                    'name' => 'Canadian Dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'ISK' => [
                $ps => [
                    'name' => 'Iceland Krona',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'AUD' => [
                $ps => [
                    'name' => 'Australian Dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'INR' => [
                $ps => [
                    'name' => 'Indian Rupee',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CHF' => [
                $ps => [
                    'name' => 'Swiss Franc',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'KRW' => [
                $ps => [
                    'name' => 'South‐Korean Won',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'DKK' => [
                $ps => [
                    'name' => 'Danish Krone',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'ZAR' => [
                $ps => [
                    'name' => 'South‐African Rand',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'SEK' => [
                $ps => [
                    'name' => 'Swedish Krona',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'RON' => [
                $ps => [
                    'name' => 'Romanian Leu New',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'NOK' => [
                $ps => [
                    'name' => 'Norwegian Krone',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'HRK' => [
                $ps => [
                    'name' => 'Croatian Kuna',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'ILS' => [
                $ps => [
                    'name' => 'Israeli Shekel',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'JOD' => [
                $ps => [
                    'name' => 'Jordanian Dinar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MYR' => [
                $ps => [
                    'name' => 'Malaysian Ringgit',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'OMR' => [
                $ps => [
                    'name' => 'Omani Rial',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'NZD' => [
                $ps => [
                    'name' => 'New Zealand Dollar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'RSD' => [
                $ps => [
                    'name' => 'Serbian Dinar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'TRY' => [
                $ps => [
                    'name' => 'New Turkish Lira',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'TND' => [
                $ps => [
                    'name' => 'Tunisian Dinar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'AED' => [
                $ps => [
                    'name' => 'Utd. Arab Emir. Dirham',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BHD' => [
                $ps => [
                    'name' => 'Bahraini Dinar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MAD' => [
                $ps => [
                    'name' => 'Moroccan Dirham',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'KWD' => [
                $ps => [
                    'name' => 'Kuwaiti Dinar',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'QAR' => [
                $ps => [
                    'name' => 'Qatari Rial',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'SAR' => [
                $ps => [
                    'name' => 'Saudi Riyal',
                    'fields' => $fields,
                    'commission' => $commissions,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
        ];
    }
}