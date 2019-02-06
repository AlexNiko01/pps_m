<?php

namespace pps\cardpay;

use api\classes\ApiError;
use pps\cardpay\models\CardToken;
use Yii;
use pps\payment\Payment;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class CardPay
 * @package pps\cardpay
 */
class CardPay extends Payment
{
    const SCI_URL_TEST = 'https://sandbox.cardpay.com/MI/cardpayment.html';
    const API_URL_TEST = 'https://sandbox.cardpay.com/MI/api/v2';

    const SCI_URL = 'https://cardpay.com/MI/cardpayment.html';
    const API_URL = 'https://cardpay.com/MI/api/v2';

    /// Транзакція успішно завершена, сума була захоплена
    const CP_STATUS_APPROVED = 'APPROVED';
    // Транзакція заборонена
    const CP_STATUS_DECLINED = 'DECLINED';
    // Транзакція успішно авторизована, але потрібен деякий час для
    // підтвердження, сума була проведена і може бути захоплена пізніше
    const CP_STATUS_PENDING = 'PENDING';
    // Транзакція була анульована (у разі повідомлення про недійсність)
    const CP_STATUS_VOIDED = 'VOIDED';
    // Трансакція була повернута (у випадку повідомлення про відшкодування)
    const CP_STATUS_REFUNDED = 'REFUNDED';
    // Заявка на повернення покупця була отримана (у випадку сповіщення про
    // відкликання платежу)
    const CP_STATUS_CHARGEBACK = 'CHARGEBACK';
    // Заявка Клієнта була відхилена, така сама, як ЗАТВЕРДЖЕНО (у випадку
    // прийнятого повідомлення про відкликання платежу)
    const CP_STATUS_CHARGEBACK_RESOLVED = 'CHARGEBACK RESOLVED';
    // Заказ был успешно отправлен в систему CardPay, и транзакция была создана
    // в системе CardPay
    const CP_ORDER_STATE_NEW = 'NEW';
    // Транзакция обрабатывается
    const CP_ORDER_STATE_IN_PROGRESS = 'IN_PROGRESS';
    // Транзакция была успешно разрешена, но требуется некоторое время
    // для проверки, сумма была проведена и может быть снята позже
    const CP_ORDER_STATE_AUTHORIZED = 'AUTHORIZED';
    // Транзакция была отклонена
    const CP_ORDER_STATE_DECLINED = 'DECLINED';
    // Транзакция была успешно завершена, сумма была зафиксирована (для платежей)
    const CP_ORDER_STATE_COMPLETED = 'COMPLETED';
    // Транзакция была отменена клиентом
    const CP_ORDER_STATE_CANCELLED = 'CANCELLED';
    // Транзакция была полностью возмещена
    const CP_ORDER_STATE_REFUNDED = 'REFUNDED';
    // Транзакция была аннулирована
    const CP_ORDER_STATE_VOIDED = 'VOIDED';
    // Требование о возврате клиента было получено
    const CP_ORDER_STATE_CHARGED_BACK = 'CHARGED_BACK';
    // Требование о возврате клиента было отклонено, равно COMPLETED
    const CP_ORDER_STATE_CHARGEBACK_RESOLVED = 'CHARGEBACK_RESOLVED';

    /**
     * @var int
     */
    private $_wallet_id;
    /**
     * @var string
     */
    private $_secret_word;
    /**
     * Whether the client accesses the testing or production system
     * @var bool
     */
    private $_is_sandbox;
    /**
     * @var string
     */
    private $_login;
    /**
     * @var string
     */
    private $_password;
    /** @var int */
    private $_errno;

    /**
     * Fill the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['wallet_id'])) {
            throw new InvalidParamException('Wallet ID empty');
        }
        if (empty($data['secret_word'])) {
            throw new InvalidParamException('Secret word empty');
        }
        if (empty($data['login'])) {
            throw new InvalidParamException('Login empty');
        }
        if (empty($data['password'])) {
            throw new InvalidParamException('Password empty');
        }

        $this->_wallet_id = $data['wallet_id'];
        $this->_secret_word = $data['secret_word'];
        $this->_login = $data['login'];
        $this->_password = $data['password'];
        $this->_is_sandbox = $data['sandbox'];
    }

    /**
     * Preliminary calculation of the invoice.
     * Get required fields for invoice.
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
     * Invoice for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        $validate = static::validateTransaction($transaction->currency, $transaction->amount, self::WAY_DEPOSIT);

        if ($validate !== true) {
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
                'message' => self::ERROR_TRANSACTION_ID,
            ];
        }

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $requests['m_out']->transaction_id = $transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        // Added transaction_id for redirection
        $params['success_url'] = $params['success_url'] . (strpos($params['success_url'], '?') ? '&' : '?') . "n={$transaction->id}";
        $params['fail_url'] = $params['fail_url'] . (strpos($params['fail_url'], '?') ? '&' : '?') . "n={$transaction->id}";

        $requisites = json_decode($transaction->requisites, true);

        $message = self::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_DEPOSIT);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $xmlSet = [
            'order' => [
                'wallet_id' => $this->_wallet_id,
                'number' => $transaction->id,
                'description' => $transaction->comment,
                'currency' => $transaction->currency,
                'amount' => $transaction->amount,
                'email' => $requisites['email'],
                'success_url' => $params['success_url'],
                'return_url' => $params['fail_url'],
                'decline_url' => $params['fail_url'],
                'cancel_url' => $params['fail_url'],
                'locale' => 'ru',
                'generate_card_token' => true
            ],
        ];

        $XML = static::_createXML($xmlSet, self::WAY_DEPOSIT);

        $query = [
            'orderXML' => base64_encode($XML),
            'sha512' => static::_getSign($XML),
        ];

        $transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $transaction->id;
        $requests['out']->data = $transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        Yii::info(['query' => $query, 'result' => []], 'payment-cardpay-invoice');

        $transaction->refund = $transaction->amount;
        $transaction->write_off = $transaction->amount;
        $transaction->status = self::STATUS_CREATED;
        $transaction->save(false);

        $answer = [
            'redirect' => [
                'method' => 'POST',
                'url' => $this->_is_sandbox ? self::SCI_URL_TEST : self::SCI_URL,
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

        if (empty($this->_secret_word)) {
            $this->logAndDie(
                "CardPay secret word is empty ({$transaction->id})",
                "Secret word is empty.",
                'CardPay callback',
                'cardpay-receive'
            );
        }

        $need = ['orderXML', 'sha512'];

        if (!self::checkRequiredParams($need, $receive)) {
            $this->logAndDie(
                "Required parameters not found ({$transaction->id})",
                [
                    'receive_data' => $receive,
                    'need' => $need
                ],
                'CardPay callback',
                'cardpay-receive'
            );
        }

        if (!$this->checkReceivedSign($receive)) {
            $this->logAndDie(
                "CardPay receive() wrong sign is received ({$transaction->id})",
                "CardPay received wrong sign",
                "CardPay callback",
                'cardpay-receive'
            );
        }

        $base64xml = $receive['orderXML'] ?? '';

        if (!$base64xml) return false;

        $xml = base64_decode($base64xml);

        $attributes = self::parseOrderXML($xml);

        $needAttrs = ['id', 'amount', 'currency', 'number', 'status'];

        if (!self::checkRequiredParams($needAttrs, $attributes)) {
            $this->logAndDie(
                "Required attributes not found ({$transaction->id})",
                [
                    'attrs' => $attributes,
                    'need' => $needAttrs
                ],
                'CardPay callback',
                'cardpay-receive'
            );
        }

        if ($transaction->way === self::WAY_DEPOSIT) {
            if (isset($attributes['card_token']) && isset($attributes['card_num'])) {
                if (!empty($transaction->buyer_id)) {
                    $result = CardToken::saveToken($transaction->brand_id, $transaction->buyer_id, $attributes['card_num'], $attributes['card_token']);
                    if (!$result) {
                        Yii::error("Can't save card_token ({$transaction->id})", 'payment-cardpay-receive');
                    }
                } else {
                    Yii::warning("Buyer ID is empty ({$transaction->id})", 'payment-cardpay-receive');
                }
            } else {
                Yii::warning([
                    'message' => "card_token or card_num not found ({$transaction->id}).",
                    'data' => $attributes,
                ], 'payment-cardpay-receive');
            }
        }

        if ((float)$transaction->amount != (float)$attributes['amount']) {
            $this->logAndDie("Cardpay receive() transaction amount not equal received amount ({$transaction->id})",
                "Transaction amount = {$transaction->amount}\nreceived amount = {$attributes['amount']}",
                "Transaction amount not equal received amount");
        }

        // If different currency
        if ($transaction->currency != $attributes['currency']) {
            $this->logAndDie("Cardpay receive() different currency ({$transaction->id})",
                "Merchant currency = {$transaction->currency}\nreceived currency = {$attributes['shop_currency']}",
                "Different currency",
                'cardpay-receive');
        }

        if (empty($transaction->external_id)) {
            $transaction->external_id = $attributes['id'];
        }

        if ($transaction->way == self::WAY_WITHDRAW) {
            self::setWithdrawStatus($transaction, $attributes['status']);
            if (!self::isFinalWithdraw($attributes['status'])) {
                $data['updateStatusJob']->transaction_id = $transaction->id;
            }
        } else {
            self::setDepositStatus($transaction, $attributes['status']);
            if (!self::isFinalDeposit($attributes['status'])) {
                $data['updateStatusJob']->transaction_id = $transaction->id;
            }
        }

        $transaction->save(false);

        return true;
    }

    /**
     * Check if the seller has enough money.
     * Get required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params): array
    {
        $validate = static::validateTransaction($params['currency'], floatval($params['amount']), self::WAY_WITHDRAW);

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
                'amount' => $params['amount'],
                'buyer_receive' => null,
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
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        $validate = static::validateTransaction($transaction->currency, $transaction->amount, self::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $requisites = json_decode($transaction->requisites, true);

        $message = self::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW);

        if ($message !== true) {
            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $answer = [];

        $transaction->status = self::STATUS_CREATED;

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID,
            ];
        }

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $requests['m_out']->transaction_id = $transaction->id;
        $requests['m_out']->data = $requests['merchant'];
        $requests['m_out']->type = 1;
        $requests['m_out']->save(false);

        $cardNumber = CardToken::hideCard($requisites['number']);

        $card = CardToken::getToken($transaction->brand_id, $transaction->buyer_id, $cardNumber);

        if (!$card) {
            return [
                'status' => 'error',
                'message' => 'card_token not found!'
            ];
        }

        $query = [
            'data' => [
                'type' => "PAYOUTS",
                'timestamp' => date("Y-m-d\\TH:i:s\\Z"),
                'merchantOrderId' => $transaction->id,
                'amount' => floatval($transaction->amount),
                'currency' => $transaction->currency,
                'email' => $requisites['email'],
                'description' => $transaction->comment,
                'cardToken' => $card->token,
            ],
        ];

        if (isset($requisites['expiryMonth'])) {
            $query['data']['card']['expiryMonth'] = $requisites['expiryMonth'];
        }

        if (isset($requisites['expiryYear'])) {
            $query['data']['card']['expiryYear'] = $requisites['expiryYear'];
        }

        if (isset($requisites['state'])) {
            $query['data']['billing']['state'] = $requisites['state'];
        }

        if (isset($requisites['country'])) {
            $query['data']['billing']['country'] = $requisites['country'];
        }

        if (isset($requisites['zip'])) {
            $query['data']['billing']['zip'] = $requisites['zip'];
        }

        if (isset($requisites['city'])) {
            $query['data']['billing']['city'] = $requisites['city'];
        }

        if (isset($requisites['street'])) {
            $query['data']['billing']['street'] = $requisites['street'];
        }

        if (isset($requisites['phone'])) {
            $query['data']['billing']['phone'] = $requisites['phone'];
        }

        $transaction->query_data = json_encode($query);

        $requests['out']->transaction_id = $transaction->id;
        $requests['out']->data = $transaction->query_data;
        $requests['out']->type = 2;
        $requests['out']->save(false);

        $result = $this->_query('payouts', $query, true, true);

        $transaction->result_data = json_encode($result);

        $transaction->save(false);

        $requests['pa_in']->transaction_id = $transaction->id;
        $requests['pa_in']->data = $transaction->result_data;
        $requests['pa_in']->type = 3;
        $requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-cardpay-withdraw');

        if (!empty($result['response']['data'])) {
            $response = $result['response']['data'];

            if (isset($response['state'])) {
                $transaction->external_id = $response['id'];
                $transaction->receive = $transaction->amount;
                $transaction->write_off = $transaction->amount;

                self::setWithdrawStatus($transaction, $response['state']);

                if (!self::isFinalWithdraw($response['state'])) {
                    $params['updateStatusJob']->transaction_id = $transaction->id;
                }

                $transaction->save(false);

                $answer['data'] = $transaction::getWithdrawAnswer($transaction);
            }

        } else if ($this->_errno == CURLE_OPERATION_TIMEOUTED) {
            $transaction->status = self::STATUS_TIMEOUT;
            $transaction->save(false);
            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else if ($this->_errno != CURLE_OK) {
            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->result_data = $this->_errno;
            $transaction->save(false);
            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else {

            $answer['message'] = self::ERROR_OCCURRED;
            $answer['status'] = 'error';

            $message = "Request url = '" . (($this->_is_sandbox) ? static::API_URL_TEST : static::API_URL);
            $message .= "\nRequest result = " . print_r($result, true);

            Yii::error($message, 'payment-cardpay-withdraw');
        }

        return $answer;
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
            if (!empty($transaction->external_id)) {
                $url = (($transaction->way == self::WAY_DEPOSIT) ? 'payments' : 'payouts') . '/' . $transaction->external_id;
                $response = $this->_query($url, [], false, true);
                $data = $response['response']['data'] ?? null;
            } else {
                $response = $data = $this->getTransactionWithoutExternalId($transaction);
                if ($response) {
                    $transaction->external_id = $response['id'];
                    $transaction->save(false);
                }
            }

            if (isset($model_req)) {
                $model_req->transaction_id = $transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            Yii::info($response, 'payment-cardpay-status');

            if ($data && isset($data['state'])) {
                if ($transaction->way == self::WAY_WITHDRAW) {
                    self::setWithdrawStatus($transaction, $data['state']);
                } else {
                    self::setDepositStatus($transaction, $data['state']);
                }

                $transaction->save(false);

                return true;
            }
        }

        return false;
    }

    /**
     * @param object $transaction
     * @return null
     */
    public function getTransactionWithoutExternalId($transaction)
    {
        $url = (($transaction->way == self::WAY_DEPOSIT) ? 'payments' : 'payouts');
        $response = $this->_query($url, [
            'startMillis' => (date('U') - 60 * 60) * 1000,
            'endMillis' => date('U') * 1000,
        ], false, true);

        if (isset($response['response']['data'])) {
            foreach ($response['response']['data'] as $trx) {
                if ($transaction->id == $trx['number']) {
                    return $trx;
                }
            }
        }

        return null;
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
     * Get query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return isset($data['n']) ? ['id' => $data['n']] : [];
    }

    /**
     * Get transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        $base64 = $data['orderXML'] ?? '';
        if (!$base64) return 0;

        $xml = base64_decode($base64);

        $attributes = self::parseOrderXML($xml);

        return $attributes['number'] ?? 0;
    }

    /**
     * @param $xml
     * @return array|mixed
     */
    public static function parseOrderXML($xml)
    {
        $parser = new \SimpleXMLElement($xml);
        return self::xmlObjToArray($parser);
    }

    /**
     * @param $obj
     * @return array|mixed
     */
    public static function xmlObjToArray($obj)
    {
        $attributes = (array)$obj;
        $array = [];
        foreach ($attributes as $key => $value) {
            if ($key == '@attributes') {
                $array = $value;
            } else {
                $array[$key] = self::xmlObjToArray($value);
            }
        }
        return $array;
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
     * @param string $way
     * @return string
     */
    public static function getResponseFormat($way = self::WAY_DEPOSIT)
    {
        return Response::FORMAT_HTML;
    }

    /**
     * Main query method
     * @param string $resource
     * @param array $params
     * @param bool $post
     * @param bool $api
     * @return array|string
     */
    private function _query(string $resource, array $params = [], $post = true, $api = false)
    {
        if ($api) {
            $url = (($this->_is_sandbox) ? static::API_URL_TEST : static::API_URL) . '/' . trim($resource, '/');
        } else {
            $url = ($this->_is_sandbox) ? static::SCI_URL_TEST : static::SCI_URL;
        }

        $headers = [
            'Authorization: Basic ' . base64_encode($this->_login . ':' . $this->_password),
            'Content-Type: application/json',
            'walletId:' . $this->_wallet_id
        ];
        $query = $post ? '' : '&' . http_build_query($params);

        $ch = curl_init($url . "?walletId=" . $this->_wallet_id . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_POST, true);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        // Now check for an HTTP error
        $curl_info = curl_getinfo($ch);
        $curl_errno = curl_errno($ch);
        $this->_errno = $curl_errno;
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (
            ($response === false) ||
            ($curl_errno > 0)
        ) {
            return 'Error number = ' . $curl_errno . '. Error text = ' . $curl_error;
        } elseif ($http_code >= 300) {
            //return "Payment system send error. HTTP Status #" . $http_code;
        }

        return [
            'info' => [
                'url' => $curl_info['url'],
                'http_code' => $curl_info['http_code'],
                'total_time' => $curl_info['total_time'],
            ],
            'response' => json_decode($response, true),
        ];
    }

    /**
     * Sign function
     * @param string $XML
     * @return string
     */
    private function _getSign(string $XML): string
    {
        return hash('sha512', $XML . $this->_secret_word);
    }

    /**
     * @param array $params
     * @return bool
     */
    private function checkReceivedSign(array $params): bool
    {
        $sign = $params["sha512"];
        $expectedSign = $this->_getSign(base64_decode($params["orderXML"]));

        if ($expectedSign != rawurldecode($sign)) {
            Yii::error("CardPay receive() wrong sign or auth is received: expectedSign = $expectedSign \nSign = $sign", 'payment-cardpay');

            return false;
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
    public static function validateTransaction(string $currency, float $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency]['cardpay'][$way]) || !$currencies[$currency]['cardpay'][$way]) {
            return "Payment system does not support '{$way}'";
        }

        return true;
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

        return $currencies[$currency]['cardpay']['fields'][$way] ?? [];
    }

    /**
     * @param $transaction
     * @param $status
     */
    public static function setDepositStatus($transaction, $status)
    {
        switch ($status) {
            case self::CP_STATUS_APPROVED:
            case self::CP_STATUS_CHARGEBACK_RESOLVED:
                $transaction->status = self::STATUS_SUCCESS;
                break;
            case self::CP_STATUS_DECLINED:
                $transaction->status = self::STATUS_ERROR;
                break;
            case self::CP_STATUS_VOIDED:
                $transaction->status = self::STATUS_VOIDED;
                break;
            case self::CP_STATUS_REFUNDED:
                $transaction->status = self::STATUS_REFUNDED;
                break;
            default:
                //$transaction->status = self::STATUS_PENDING;
        }
    }

    /**
     * @param $transaction
     * @param $status
     */
    public static function setWithdrawStatus($transaction, $status)
    {
        switch ($status) {
            case self::CP_ORDER_STATE_COMPLETED:
            case self::CP_STATUS_CHARGEBACK_RESOLVED:
                $transaction->status = self::STATUS_SUCCESS;
                break;
            case self::CP_STATUS_DECLINED:
                $transaction->status = self::STATUS_ERROR;
                break;
            case self::CP_ORDER_STATE_CANCELLED:
                $transaction->status = self::STATUS_CANCEL;
                break;
            case self::CP_STATUS_VOIDED:
                $transaction->status = self::STATUS_VOIDED;
                break;
            case self::CP_STATUS_REFUNDED:
                $transaction->status = self::STATUS_REFUNDED;
                break;
            default:
                // $transaction->status = self::STATUS_PENDING;
        }
    }

    /**
     * @param $status
     * @return bool
     */
    public static function isFinalDeposit($status): bool
    {
        $final = [
            self::CP_STATUS_APPROVED,
            self::CP_STATUS_DECLINED,
            self::CP_STATUS_VOIDED,
            self::CP_STATUS_REFUNDED,
            self::CP_ORDER_STATE_COMPLETED,
        ];

        return in_array($status, $final);
    }

    /**
     * @param $status
     * @return bool
     */
    public static function isFinalWithdraw($status): bool
    {
        $final = [
            self::CP_ORDER_STATE_COMPLETED,
            self::CP_STATUS_DECLINED,
            self::CP_STATUS_VOIDED,
            self::CP_ORDER_STATE_CANCELLED,
            self::CP_STATUS_REFUNDED,
            self::CP_STATUS_CHARGEBACK_RESOLVED,
        ];

        return in_array($status, $final);
    }

    /******************************** XML Function ********************************/

    /**
     * XML creation function for request to PS
     * @param array $dataSet
     * @param string $ways
     * @return string
     */
    private static function _createXML(array $dataSet, string $ways = self::WAY_DEPOSIT): string
    {
        $xml = '';
        $ValidsFields = [
            self::WAY_DEPOSIT => [
                'required' => [
                    'order' => [
                        'wallet_id',
                        'number',
                        'description',
                        'currency',
                        'amount',
                        'email',
                    ],
                ],
                'requiredIf' => [
                    'order' => [
                        'shipping' => [
                            'country',
                        ],
                        'items' => [
                            'name',
                        ],
                    ],
                ]
            ],
            self::WAY_WITHDRAW => [
                'required' => [
                    'order' => [
                        'wallet_id',
                        'number',
                        'description',
                        'currency',
                        'amount',
                        'email',
                        'card' => [
                            'num',
                            /*
                                                        'holder',
                                                        'expires',
                                                        'cvv',
                             */
                        ],
                        'billing' => [
                            'country',
                            /*
                                                        'zip',
                                                        'city',
                                                        'street',
                                                        'phone',
                             */
                        ],
                    ],
                ],
                'requiredIf' => [
                    'order' => [
                        'shipping' => [
                            'country',
                        ],
                        'items' => [
                            'name',
                        ],
                    ],
                ]
            ]
        ];

        $Fields = $ValidsFields[$ways];

        foreach ($Fields['required'] as $order => $orderField) {
            if (
                (isset($dataSet[$order])) &&
                (sizeof($dataSet[$order]) > 0)
            ) {
                foreach ($orderField as $key1 => $field1) {
                    if (is_array($field1)) {
                        foreach ($field1 as $field2) {
                            if (
                                !isset($dataSet[$order][$key1][$field2]) ||
                                empty($dataSet[$order][$key1][$field2])
                            ) {
                                Yii::error("CardPay _createXML() Empty field value '$order => $key1 => $field2'", 'payment-cardpay');
                            }
                        }
                    } else {
                        if (
                            !isset($dataSet[$order][$field1]) ||
                            empty($dataSet[$order][$field1])
                        ) {
                            Yii::error("CardPay _createXML() Empty field value '$order => $field1'", 'payment-cardpay');
                        }
                    }
                }
            } else {
                Yii::error("CardPay _createXML() Empty XML structure $order (way: " . $ways . ")", 'payment-cardpay');
            }
        }

        foreach ($Fields['requiredIf'] as $order => $orderField) {
            if (
                (!isset($dataSet[$order])) ||
                (empty($dataSet[$order]))
            ) {
                foreach ($orderField as $key1 => $field1) {
                    if (is_array($field1)) {
                        foreach ($field1 as $field2) {
                            if (
                                !isset($dataSet[$order][$key1][$field2]) ||
                                empty($dataSet[$order][$key1][$field2])
                            ) {
                                Yii::error("CardPay _createXML() Empty field value '$order => $key1 => $field2'", 'payment-cardpay');
                            }
                        }
                    } else {
                        if (
                            !isset($dataSet[$order][$field1]) ||
                            empty($dataSet[$order][$field1])
                        ) {
                            Yii::error("CardPay _createXML() Empty field value '$order => $field1'", 'payment-cardpay');
                        }
                    }
                }
            }
        }

        foreach ($Fields['requiredIf'] as $order => $orderField) {
            if (is_array($orderField)) {
                foreach ($orderField as $key1 => $field1) {
                    if (is_array($field1)) {
                        foreach ($field1 as $key2 => $field2) {
                            if (
                                isset($dataSet[$order][$key1][0]) &&
                                !empty($dataSet[$order][$key1][0])
                            ) {
                                if (is_array($dataSet[$order][$key1][0])) {
                                    foreach ($dataSet[$order][$key1] as $value) {
                                        if (
                                            !isset($value[$field2]) ||
                                            empty($value[$field2])
                                        ) {
                                            Yii::error("CardPay _createXML() Empty field value '$order => $key1 => $key2 => $field2'", 'payment-cardpay');
                                        }
                                    }
                                } else {
                                    if (
                                        !isset($dataSet[$order][$key1][$field2]) ||
                                        empty($dataSet[$order][$key1][$field2])
                                    ) {
                                        Yii::error("CardPay _createXML() Empty field value '$order => $key1 => $field2'", 'payment-cardpay');
                                    }
                                }
                            }
                        }
                    } else {
                        if (
                            !isset($dataSet[$order][$field1]) ||
                            empty($dataSet[$order][$field1])
                        ) {
                            Yii::error("CardPay _createXML() Empty field value '$order => $field1'", 'payment-cardpay');
                        }
                    }
                }
            }
        }

        if (isset($dataSet['order'])) {
            $xml .= '<order';
            $xml_card = '';
            $xml_billing = '';
            $xml_shipping = '';
            $xml_items = '';

            foreach ($dataSet['order'] as $key => $value) {
                if (!is_array($value)) {
                    $xml .= ' ' . $key . '="' . $value . '"';
                }
            }

            if (isset($dataSet['order']['card'])) {
                $xml_card .= "<card";
                foreach ($dataSet['order']['card'] as $key => $value) {
                    if (!is_array($value)) {
                        $xml_card .= ' ' . $key . '="' . $value . '"';
                    }
                }
                $xml .= "/>";
            }

            if (isset($dataSet['order']['billing'])) {
                $xml_billing .= "<billing";
                foreach ($dataSet['order']['billing'] as $key => $value) {
                    if (!is_array($value)) {
                        $xml_billing .= ' ' . $key . '="' . $value . '"';
                    }
                }
                $xml_billing .= "/>";
            }

            if (isset($dataSet['order']['shipping'])) {
                $xml_shipping .= "<shipping";
                foreach ($dataSet['order']['shipping'] as $key => $value) {
                    if (!is_array($value)) {
                        $xml_shipping .= ' ' . $key . '="' . $value . '"';
                    }
                }
                $xml_shipping .= "/>";
            }

            if (isset($dataSet['order']['items'])) {
                $xml_items .= "<items>";
                foreach ($dataSet['order']['items'] as $item) {
                    $xml_items .= "<item";
                    foreach ($item as $key => $value) {
                        if (!is_array($value)) {
                            $xml_items .= ' ' . $key . '="' . $value . '"';
                        }
                    }
                    $xml_items .= "/>";
                }
                $xml_items .= "</items>";
            }

            if (
                (strlen($xml_card) > 0) ||
                (strlen($xml_billing) > 0) ||
                (strlen($xml_shipping) > 0) ||
                (strlen($xml_items) > 0)
            ) {
                if (strlen($xml_card) > 0) {
                    $xml .= $xml_card;
                }
                if (strlen($xml_billing) > 0) {
                    $xml .= $xml_billing;
                }
                if (strlen($xml_shipping) > 0) {
                    $xml .= $xml_shipping;
                }
                if (strlen($xml_items) > 0) {
                    $xml .= $xml_items;
                }

                $xml .= "</order>";
            } else {
                $xml .= "/>";
            }
        }

        return $xml;
    }

    /**
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
     * @param bool $getListCurr
     * @return array
     */
    public static function getApiFields(bool $getListCurr = true): array
    {
        $currencyList = $getListCurr ? static::getCurrenciesList() : [];

        return [
            'cardpay' => [
                self::WAY_DEPOSIT => [
                    'email' => [
                        'required' => true,
                        'label' => 'Customer email address',
                        'regex' => '^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$',
                        'currencies' => $currencyList,
                    ],
//                    'success_url' => [
//                        'required' => true,
//                        'label' => 'Redirection URL after successful payment',
//                        'regex' => '^http[s]{0,1}:[\/]{2}www{0,1}.[a-zA-Z0-9.\/\-_+&=?]+$',
//                        'currencies' => $currencyList,
//                    ],
//                    'fail_url' => [
//                        'required' => true,
//                        'label' => 'Redirection URL after cancelled or failed payment',
//                        'regex' => '^http[s]{0,1}:[\/]{2}www{0,1}.[a-zA-Z0-9.\/\-_+&=?]+$',
//                        'currencies' => $currencyList,
//                    ],
                ],
                self::WAY_WITHDRAW => [
                    'email' => [
                        'required' => true,
                        'label' => 'Customer email address',
                        'regex' => '^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$',
                        'currencies' => $currencyList,
                    ],
                    'number' => [
                        'required' => true,
                        'label' => 'Card number',
                        'regex' => '^[0-9]{13,19}$',
                        'currencies' => $currencyList,
                    ],
                    /*'holder' => [
                        'label' => 'The card holder',
                        'regex' => '^[a-zA-Z.-]{3,25} [a-zA-Z.-]{3,25}$',
                        'required' => true,
                        'currencies' => $currencyList,
                    ],*/
                    /*'country' => [
                        'required' => true,
                        'label' => 'Country',
                        'regex' => '^[A-Z]{2,3}|[0-9]{3}$',
                        'currencies' => $currencyList,
                    ],*/
                    /*'expiryMonth' => [
                        'label' => 'Expiry Month',
                        'regex' => '^(0[1-9]|1[0-2])$',
                        'required' => true,
                        'currencies' => $currencyList,
                    ],
                    'expiryYear' => [
                        'label' => 'Expiry Year',
                        'regex' => '^20(1[8-9]|[2-9][0-9])$',
                        'required' => true,
                        'currencies' => $currencyList,
                    ],*/
                ]
            ],
        ];
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
     * Get supported currencies
     * @param bool $getListCurr
     * @param string $ps
     * @return array
     */
    public static function getSupportedCurrencies(bool $getListCurr = true, $ps = 'cardpay'): array
    {
        $apiFields = static::getApiFields($getListCurr);
        $fields = $apiFields[$ps] ?? [];

        require_once __DIR__ . '/currencies.php';

        return getCurrencies($fields, $ps);
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
                if (!in_array($field, array_keys($requisites)) && in_array($currency, $methods[$method][$way][$field]['currencies'])) {
                    return "Required param '{$field}' not found";
                }
            }
        }

        return true;
    }
}
