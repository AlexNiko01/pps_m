<?php

namespace pps\paysafecard;

use api\classes\ApiError;
use Yii;
use pps\payment\Payment;
use yii\base\InvalidParamException;
use yii\db\Exception;
use yii\web\Response;

/**
 * Class PaySafeCard
 * @package pps\paysafecard
 */
class PaySafeCard extends Payment
{
    // Початковий стан платежу після його успішного створення.
    const PSC_STATUS_INITIATED = 'INITIATED';
    // Клієнт був перенаправлений до платіжної панелі paysafecard для авторизації платежу.
    const PSC_STATUS_REDIRECTED = 'REDIRECTED';
    // Клієнт уповноважив оплату.
    const PSC_STATUS_AUTHORIZED = 'AUTHORIZED';
    const PSC_STATUS_SUCCESS = 'SUCCESS';
    const PSC_STATUS_CANCELED_MERCHANT = 'CANCELED_MERCHANT';
    const PSC_STATUS_CANCELED_CUSTOMER = 'CANCELED_CUSTOMER';
    // Клієнт не дозволив здійснити платіж у період часу розташування, або ви,
    // діловий партнер, не зафіксували авторизовану суму протягом періоду розміщення
    const PSC_STATUS_EXPIRED = 'EXPIRED';

    const API_URL = 'https://api.paysafecard.com/v1';
    const API_URL_TEST = 'https://apitest.paysafecard.com/v1';

    /**
     * @var object
     */
    private $_transaction;
    /**
     * @var object
     */
    private $_requests;
//    /**
//     * @var string
//     */
//    private $_accountNum;
//    /**
//     * @var string
//     */
//    private $_storeID;
//    /**
//     * @var string
//     */
//    private $_storePwd;
    /**
     * @var string
     */
    private $_customer_id;
    /**
     * @var string
     */
    private $_key;
    /**
     * Whether the client accesses the testing or production system
     *
     * @var bool
     */
    private $_testing;
    private $_curl;
    private $_receive;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        /*if (empty($data['accountNum'])) {
            throw new InvalidParamException('accountNum empty');
        }
        if (empty($data['storeID'])) {
            throw new InvalidParamException('storeID empty');
        }
        if (empty($data['storePwd'])) {
            throw new InvalidParamException('storePwd empty');
        }*/
        if (empty($data['customer_id'])) {
            throw new InvalidParamException('customer_id empty');
        }
        if (empty($data['key'])) {
            throw new InvalidParamException('key empty');
        }

//        $this->_accountNum = $data['accountNum'];
//        $this->_storeID = $data['storeID'];
//        $this->_storePwd = $data['storePwd'];
        $this->_customer_id = $data['customer_id'];
        $this->_key = $data['key'];
        $this->_testing = $data['testing'] ?? false;
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $validate = static::validateTransaction($params['currency'], $params['amount'],self::WAY_DEPOSIT);

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
     * @return array|bool
     */
    public function invoice(array $params): array
    {
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];

        $validate = static::validateTransaction($this->_transaction['currency'], $this->_transaction['amount'], self::WAY_DEPOSIT);

        if (!$validate) {
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

        $data = json_decode($this->_requests['merchant'], true);

        $customer = [
            "id" => $this->_customer_id,
            "ip" => $this->_getIP(),
        ];

        if (!empty($data['country_restriction'])) {
            array_push($customer, "country_restriction", $data['country_restriction']);
        }

        if (!empty($data['kyc_restriction'])) {
            array_push($customer, "kyc_level", $data['kyc_restriction']);
        }

        if (!empty($data['min_age'])) {
            array_push($customer, "min_age", $data['min_age']);
        }

        $query = [
            "amount" => str_replace(',', '.', $this->_transaction->amount),
            "currency" => $this->_transaction->currency,
            "customer" => $customer,
            "redirect" => [
                "success_url" => $params['success_url'],
                "failure_url" => $params['fail_url'],
            ],
            "type" => "PAYSAFECARD",
            "notification_url" => $params['callback_url'],
//            "shop_id"          => $shop_id,
        ];

        if (!empty($data['submerchant_id'])) {
            array_push($query, "submerchant_id", $data['submerchant_id']);
        }

        $headers = [];

        if (!empty($data['correlation_id'])) {
            $headers = ["Correlation-ID: " . $data['correlation_id']];
        }

        $this->_transaction->query_data = json_encode($query);

        $this->_requests['out']->transaction_id = $this->_transaction->id;
        $this->_requests['out']->data = $this->_transaction->query_data;
        $this->_requests['out']->type = 2;
        $this->_requests['out']->save(false);

        Yii::info(['query' => $query, 'result' => []], 'payment-paysafecard-invoice');

        $result = $this->_query('payments', $query, true, $headers);


        $this->_transaction->result_data = json_encode($result);

        $this->_requests['pa_in']->transaction_id = $this->_transaction->id;
        $this->_requests['pa_in']->data = $this->_transaction->result_data;
        $this->_requests['pa_in']->type = 3;
        $this->_requests['pa_in']->save(false);

        Yii::info(['query' => $query, 'result' => $result], 'payment-paysafecard-invoice');

        if ($this->requestIsOk()) {
            return [
                'redirect' => [
                    'method' => 'POST',
                    'url' => $result['redirect']['auth_url'], //static::SCI_URL,
                    'params' => [], //$result, //$redirectParams,
                ],
                'data' => $this->_transaction::getDepositAnswer($this->_transaction)
            ];
        } elseif ($this->_curl["error_nr"] != CURLE_OK) {
            $this->_transaction->status = self::STATUS_ERROR;
            $this->_transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;
        }


        return [
            'status' => 'error',
            'message' => "Request is bad
curl =>" . print_r($this->_curl, true) . "
result =>" . print_r($result, true) . "
"
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
        if (!isset($this->_receive['id'])) {
            // If id not set
            $this->logAndDie(
                'PaySafeCard receive() id is not set',
                "Transaction id is not set!",
                "Transaction id is not set"
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
        if (isset($this->_receive['status'])) {
            if (static::isAuthorized($this->_receive['status'])) {
                $payments = $this->_query('payments/' . $this->_receive['id'] . '/capture');

                if (!$this->requestIsOk()) {
                    return false;
                }

                if ($this->isSuccessful($payments['data']['status'])) {
                    $this->_transaction->status = self::STATUS_SUCCESS;
                } elseif ($this->isExpired($payments['data']['status'])) {
                    $this->_transaction->status = self::STATUS_ERROR;
                } elseif ($this->isCancelled($payments['data']['status'])) {
                    $this->_transaction->status = self::STATUS_CANCEL;
                }
            }
        }

        if (isset($this->_receive['id'])) {
            $this->_transaction->external_id = $this->_receive['id'];
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
    public function withDraw(array $params)
    {
        $this->_transaction = $params['transaction'];
        $this->_requests = $params['requests'];
        $merch_params = json_decode($this->_requests['merchant'], true);
        $answer = [];

        $validate = $this->validateTransaction($this->_transaction->currency, $this->_transaction->amount, self::WAY_WITHDRAW);

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
            'type' => 'PAYSAFECARD',
            'capture' => true,
            'amount' => $this->_transaction->amount,
            'currency' => $this->_transaction->currency,
            'customer' => [
                'id' => $this->_transaction->brand_id . '_' . $this->_transaction->payment_system_id,
                'email' => $merch_params['requisites']['email'] ?? '',
                'date_of_birth' => $merch_params['requisites']['date_of_birth'] ?? '',
                'first_name' => $merch_params['requisites']['first_name'] ?? '',
                'last_name' => $merch_params['requisites']['last_name'] ?? '',
            ],
        ];

        $preWithdrawResponse = $this->_query('payouts', $preWithdrawQuery);
        Yii::info([
            'query' => $preWithdrawQuery,
            'result' => $preWithdrawResponse
        ], 'payment-paysafecard');

        if ($this->requestIsOk()) {
            if (static::isSuccessful($preWithdrawResponse['status'])) {
                $this->_transaction->query_data = json_encode([
                    'prepare' => $preWithdrawQuery,
                    'transfer' => [],
                ]);

                $this->_requests['out']->transaction_id = $this->_transaction->id;
                $this->_requests['out']->data = $this->_transaction->query_data;
                $this->_requests['out']->type = 2;
                $this->_requests['out']->save(false);

                $withdrawResponse = $this->_query('payouts/' . $preWithdrawResponse['id'] . '/capture');

                $this->_transaction->result_data = json_encode([
                    'prepare' => $preWithdrawResponse,
                    'transfer' => $withdrawResponse
                ]);

                $this->_requests['pa_in']->transaction_id = $this->_transaction->id;
                $this->_requests['pa_in']->data = $this->_transaction->result_data;
                $this->_requests['pa_in']->type = 3;
                $this->_requests['pa_in']->save(false);

                Yii::info([
                    'query' => [],
                    'result' => $withdrawResponse
                ],
                    'payment-paysafecard-withdraw'
                );

                if ($this->requestIsOk()) {
                    if (static::isSuccessful($withdrawResponse['status'])) {
                        $this->_transaction->external_id = $withdrawResponse['id'];
                        $this->_transaction->receive = round(floatval($withdrawResponse['amount']), 2);
                        $this->_transaction->status = self::STATUS_SUCCESS;
                        $this->_transaction->save(false);

                        $answer['data'] = [
                            'id' => $this->_transaction->id,
                            'transaction_id' => $this->_transaction->merchant_transaction_id,
                            'status' => $this->_transaction->status,
                            'buyer_receive' => $this->_transaction->receive,
                            'amount' => number_format($this->_transaction->amount, 2),
                            'currency' => $this->_transaction->currency,
//                            'commission'       => 'unknown',
//                            'commission_payer' => $commission_payer,
                        ];
                    } else {
                        $this->_transaction->status = self::STATUS_ERROR;
                        $this->_transaction->save();

                        $answer = [
                            'status' => 'error',
                            'message' => static::getError($withdrawResponse) ?? self::ERROR_OCCURRED . ' (4)',
                        ];

                        $message = "Request url = " . self::API_URL . '/withdraw';
                        $message .= "\nRequest result = " . print_r($withdrawResponse, true);

                        Yii::error($message, 'payment-paysafecard-withdraw');
                    }
                } else {
                    $answer = [
                        'status' => 'error',
                        'message' => static::getError($withdrawResponse) ?? self::ERROR_OCCURRED . ' (3)',
                    ];
                }
            } else {
            $answer = [
                'status' => 'error',
                'message' => static::getError($preWithdrawResponse) ?? self::ERROR_OCCURRED . ' (2)',
            ];
        }
        } elseif ($this->_curl["error_nr"] == CURLE_OPERATION_TIMEOUTED) {
            $this->_transaction->status = self::STATUS_TIMEOUT;
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);

        } elseif ($this->_curl["error_nr"] != CURLE_OK) {
            $this->_transaction->status = self::STATUS_NETWORK_ERROR;
            $this->_transaction->result_data = $this->_curl["error_nr"];
            $this->_transaction->save(false);
            $params['updateStatusJob']->transaction_id = $this->_transaction->id;

            $answer['data'] = $this->_transaction::getWithdrawAnswer($this->_transaction);
        } else {
            $answer = [
                'status' => 'error',
                'message' => static::getError($preWithdrawResponse) ?? self::ERROR_OCCURRED . ' (1)',
            ];
        }

        return $answer;
    }

    /**
     * Update not final statuses for deposit
     * @param object $transaction
     * @param null|object $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        $this->_transaction = $transaction;

        if (in_array($transaction->status, self::getNotFinalStatuses())) {
            if ($this->_transaction->way == self::WAY_DEPOSIT) {
                $url = "payments";
            } elseif ($this->_transaction->way == self::WAY_WITHDRAW) {
                $url = "payouts";
            } else {
                return false;
            }

            $response = $this->_query($url . '\\' . $transaction->external_id);

            if (isset($model_req)) {
                // Saving the data that came from the PA in the unchanged state
                $model_req->transaction_id = $this->_transaction->id;
                $model_req->data = json_encode($response);
                $model_req->type = 8;
                $model_req->save(false);
            }

            Yii::info($response, 'status-paysafecard-' . $this->_transaction->way);

            if (isset($response['status'])) {
                if ($this->isFinal($response['status'])) {
                    if ($this->isSuccessful($response['status'])) {
                        $this->_transaction->status = self::STATUS_SUCCESS;
                    } elseif ($this->isExpired($response['status'])) {
                        $this->_transaction->status = self::STATUS_ERROR;
                    } elseif ($this->isCancelled($response['status'])) {
                        $this->_transaction->status = self::STATUS_CANCEL;
                    }
                } else {
                    $this->_transaction->status = self::STATUS_PENDING;
                }

                $this->_transaction->save(false);

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $resource
     * @param array $params
     * @param bool $post
     * @param array $headers
     * @return array
     */
    private function _query(string $resource, $params = [], $post = false, $headers = [])
    {
        $header = [
            "Authorization: Basic " . base64_encode($this->_key),
            "Content-Type: application/json",
        ];

        $url = (($this->_testing) ? static::API_URL_TEST : static::API_URL) . '/' . $resource;
        if (!$post) {
            if (!empty($params)) {
                $url .= '?' . $params;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($header, $headers));
        curl_setopt($ch, CURLOPT_POST, $post);

        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response = curl_exec($ch);

        $this->_curl["info"] = curl_getinfo($ch);
        $this->_curl["http_status"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->_curl["error_nr"] = curl_errno($ch);
        $this->_curl["error_text"] = curl_error($ch);
        curl_close($ch);

        return [
            'info' => $this->_curl["info"],
            'headers' => substr($response, 0, $this->_curl["info"]['header_size']),
            'body' => substr($response, -$this->_curl["info"]['size_download']),
            'http_status' => $this->_curl["http_status"],
            'data' => json_decode($response, true),
        ];
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
        return isset($data['id']) ? ['external_id' => $data['id']] : [];
    }

    /**
     * Get transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['id'] ?? 0;
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
     * Get fields for filling
     * @param string $currency
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $way): array
    {
        $currencies = static::getSupportedCurrencies();

        return $currencies[$currency]['paysafecard']['fields'][$way] ?? [];
    }

    /**
     * @return string
     */
    private function _getIP(): string
    {
        return ((isset($_SERVER['HTTP_X_REAL_IP'])) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR']);
    }

    /**
     * check request status
     * @return bool
     */
    public function requestIsOk()
    {
        return (($this->_curl["error_nr"] == 0) && ($this->_curl["http_status"] < 300));
    }

    /**
     * Check final status
     * @param string $status
     * @return bool
     */
    private static function isFinal(string $status): bool
    {
        $final_status = [
            static::PSC_STATUS_SUCCESS,
            static::PSC_STATUS_CANCELED_MERCHANT,
            static::PSC_STATUS_CANCELED_CUSTOMER,
            static::PSC_STATUS_EXPIRED
        ];

        return in_array($status, $final_status);
    }

    /**
     * @return bool
     */
    public static function isInitiated(string $status)
    {
        return ($status === static::PSC_STATUS_INITIATED);
    }

    /**
     * @return bool
     */
    public static function isRedirected(string $status)
    {
        return ($status === static::PSC_STATUS_REDIRECTED);
    }

    /**
     * @param string $status
     * @return bool
     */
    public static function isCancelled(string $status)
    {
        return (
            ($status === static::PSC_STATUS_CANCELED_CUSTOMER) ||
            ($status === static::PSC_STATUS_CANCELED_MERCHANT)
        );
    }

    /**
     * @param string $status
     * @return bool
     */
    public static function isExpired(string $status)
    {
        return ($status === static::PSC_STATUS_EXPIRED);
    }

    /**
     * @param string $status
     * @return bool
     */
    public static function isAuthorized(string $status)
    {
        return ($status === static::PSC_STATUS_AUTHORIZED);
    }

    /**
     * @param string $status
     * @return bool
     */
    public static function isSuccessful(string $status)
    {
        return ($status === static::PSC_STATUS_SUCCESS);
    }

    /**
     * Shorthand for all statuses that indicate a failed payment (cancelled + expired)
     * @param string $status
     * @return bool
     */
    public static function isFailed(string $status)
    {
        return (
            static::isCancelled($status) ||
            static::isExpired($status)
        );
    }

    /**
     * Shorthand for all statuses that indicate a payment waiting to be authorized
     * @return bool
     */
    public static function isWaiting(string $status)
    {
        return (
            static::isInitiated($status) ||
            static::isRedirected($status)
        );
    }

    /**
     * Validation transaction before send to payment system
     * @param string $currency
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    public static function validateTransaction(string $currency, $amount, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency]['paysafecard'][$way]) || !$currencies[$currency]['paysafecard'][$way]) {
            return "Payment system does not support '{$way}'";
        }

        if ($amount <= 0) {
            self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
            return "Amount should to be more than '0'";
        }

        return true;
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
            'paysafecard' => [
                self::WAY_DEPOSIT => [],
                self::WAY_WITHDRAW => [
                    'email' => [
                        'required' => true,
                        'label' => 'Customer email address',
                        'regex' => '^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$',
                        'currencies' => $currencyList,
                    ],
                    'birth' => [
                        'required' => true,
                        'label' => 'Date of birth of the customer',
                        'regex' => '^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$',
                        'currencies' => $currencyList,
                    ],
                    'first_name' => [
                        'required' => true,
                        'label' => 'Customer name',
                        'regex' => '^[a-zA-Z]+$',
                        'currencies' => $currencyList,
                    ],
                    'last_name' => [
                        'required' => true,
                        'label' => 'Surname of the customer',
                        'regex' => '^[a-zA-Z]+$',
                        'currencies' => $currencyList,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $Errors
     * @return string
     */
    public static function getError($Errors)
    {
        $err_data = $Errors['data'];
        $http_info = $Errors['info'];
        $number = $err_data["number"] ?? 0;
        $err_code = $err_data["code"] ?? '';

        if (!empty($err_code)) {
            if (isset($err_data["message"])) {
                return (isset($err_data["param"]) ? ("Params " . $err_data["param"] . ". ") : '') . $err_data["message"];
            }
        }

        switch ($http_info['http_code']) {
            case 400:
                switch ($number) {
                    case 2001:
                        return 'Duplicate transaction ID';
                    case 2017:
                        return 'Payment invalid state';
                    case 3001:
                        return 'Merchant with Id XXXXXXXXXX is not active';
                    case 3007:
                        return 'Merchant with Id XXXXXXXXXX is not allowed to ' .
                            'perform this debit any more';
                    case 3014:
                        return 'Submerchant not found';
                    case 3103:
                        return 'Duplicate order request';
                    case 3106:
                        return 'Facevalue format error';
                    case 3150:
                        return 'Missing parameter.';
                    case 3151:
                        return 'Invalid currency';
                    case 3161:
                        return 'Merchant not allowed for payout.';
                    case 3162:
                        return 'Mypsc account not found. Unfortunately, no my ' .
                            'paysafecard account exists under the e-mail ' .
                            'address you have entered. Please check the ' .
                            'address for a typing error. If you do not have ' .
                            'a my paysafecard account, you can register for ' .
                            'one online now for free.';
                    case 3163:
                        return 'Invalid parameter';
                    case 3164:
                        return 'Duplicate payout request.';
                    case 3165:
                        return 'Invalid amount.';
                    case 3166:
                        return 'Merchant limit reached.';
                    case 3167:
                        return 'Customer balance exceeded. Unfortunately, the ' .
                            'payout could not be completed due to a problem ' .
                            'which has arisen with your my paysafecard ' .
                            'account. paysafecard has already sent you an ' .
                            'e-mail with further information on this. ' .
                            'Please follow the instructions found in this ' .
                            'e-mail before requesting the payout again.';
                    case 3168:
                        return 'Kyc invalid for payout customer. Unfortunately, ' .
                            'the payout could not be completed due to a ' .
                            'problem which has arisen with your my ' .
                            'paysafecard account. paysafecard has already ' .
                            'sent you an e-mail with further information on ' .
                            'this. Please follow the instructions found in ' .
                            'this e-mail before requesting the payout again.';
                    case 3169:
                        return 'Payout id collision.';
                    case 3170:
                        return 'Topup limit exceeded. Unfortunately, the payout ' .
                            'could not be completed due to a problem which ' .
                            'has arisen with your my paysafecard account. ' .
                            'paysafecard has already sent you an e-mail ' .
                            'with further information on this. Please ' .
                            'follow the instructions found in this e-mail ' .
                            'before requesting the payout again.';
                    case 3171:
                        return 'Payout amount below minimum.';
                    case 3179:
                        return 'Merchant refund exceeds original transaction';
                    case 3180:
                        return 'Merchant refund original transaction invalid state';
                    case 3181:
                        return 'Merchant refund client ID not matching';
                    case 3182:
                        return 'No unload merchant configured';
                    case 3193:
                        return 'Customer inactive.';
                    case 3194:
                        return 'Customer yearly payout limit reached. ' .
                            'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. paysafecard ' .
                            'has already sent you an e-mail with further ' .
                            'information on this. Please follow the ' .
                            'instructions found in this e-mail before ' .
                            'requesting the payout again.';
                    case 3195:
                        return 'Customer details mismatched. The personal ' .
                            'details associated with your my paysafecard ' .
                            'account do not match the details of this ' .
                            'account. Please check the first names, surnames ' .
                            'and dates of birth entered in both accounts and ' .
                            'request the payout again.';
                    case 3197:
                        return 'Max payout merchant clients assigned. ' .
                            'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. paysafecard ' .
                            'has already sent you an e-mail with further ' .
                            'information on this. Please follow the ' .
                            'instructions found in this e-mail before ' .
                            'requesting the payout again.';
                    case 3198:
                        return 'Max amount of payout merchants reached. ' .
                            'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. Please ' .
                            'contact the paysafecard support team. ' .
                            'info@paysafecard.com';
                    case 3199:
                        return 'Payout blocked.';
                    case 3230:
                        return 'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. paysafecard ' .
                            'has already sent you an e-mail with further ' .
                            'information on this. Please follow the ' .
                            'instructions found in this e-mail before ' .
                            'requesting the payout again.';
                    case 3231:
                        return 'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. paysafecard ' .
                            'has already sent you an e-mail with further ' .
                            'information on this. Please follow the ' .
                            'instructions found in this e-mail before ' .
                            'requesting the payout again.';
                    case 3232:
                        return 'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. paysafecard ' .
                            'has already sent you an e-mail with further ' .
                            'information on this. Please follow the ' .
                            'instructions found in this e-mail before ' .
                            'requesting the payout again.';
                    case 3233:
                        return 'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. paysafecard ' .
                            'has already sent you an e-mail with further ' .
                            'information on this. Please follow the ' .
                            'instructions found in this e-mail before ' .
                            'requesting the payout again.';
                    case 3234:
                        return 'Unfortunately, the payout could not be ' .
                            'completed due to a problem which has arisen ' .
                            'with your my paysafecard account. paysafecard ' .
                            'has already sent you an e-mail with further ' .
                            'information on this. Please follow the ' .
                            'instructions found in this e-mail before ' .
                            'requesting the payout again.';
                    case 10028:
                        return 'Invalid API Key';
                    default:
                        return 'HTTP:400 Bad Request. Missing parameter. ' .
                            'Please check logs.';
                }
            case 401:
                return ($number == 10008) ? 'Invalid API Key' : 'HTTP:401 Unauthorized. Invalid or expired API ' .
                    'key. Please check logs.';
            case 403:
                return 'HTTP:403 Transaction could not be initiated due to ' .
                    'connection problems. The IP from the server is not ' .
                    'whitelisted! Server IP:' . $_SERVER["SERVER_ADDR"];
            case 404:
                switch ($number) {
                    case 3100:
                        return 'Product not available';
                    case 3162:
                        return 'Customer not found';
                    case 3184:
                        return 'Merchant refund missing transaction';
                    case 3185:
                        return 'Merchant refund customer credentials missing';
                    default:
                        return 'HTTP:400 Not Found.This is also returned when ' .
                            'you try to retrieve a payment that does not ' .
                            'exist. Please check logs.';
                }
            case 500:
                return $number == 10007 ? 'Invalid API Key' : 'HTTP:500 Internal Server Error. This indicates ' .
                    'a general technical error on paysafecard\'s ' .
                    'end. Please check logs.';
            case 501:
                return 'HTTP:501 Not Implemented. Version feature not ' .
                    'implemented. Please check logs.';
            case 502:
                return 'HTTP:502 Bad Gateway. Invalid response from upstream ' .
                    'system. Please check logs.';
            case 503:
                return 'HTTP:503 Service Unavailable. Server overloaded. ' .
                    'Please check logs.';
            case 504:
                return 'HTTP:504 Gateway Timeout. Timeout from upstream ' .
                    'system. Please check logs.';
        }

        if ($number > 0) {
            switch ($number) {
                // Deposit
                case 4003:
                    return 'The amount for this transaction exceeds the ' .
                        'maximum amount. The maximum amount is ' .
                        '1000 EURO (equivalent in other currencies)';
                case 3001:
                    return 'Transaction could not be initiated because ' .
                        'the account is inactive.';
                case 2002:
                    return 'payment id is unknown.';
                case 2010:
                    return 'Currency is not supported.';
                case 2029:
                    return 'Amount is not valid. Valid amount has to be above 0.';
                // Withdraw
                case 3162:
                    return 'Unfortunately, no my paysafecard account exists ' .
                        'under the e-mail address you have entered. Please ' .
                        'check the address for a typing error. If you do ' .
                        'not have a my paysafecard account, you can ' .
                        'register for one online now for free.';
                case 3195:
                    return 'The personal details associated with your my ' .
                        'paysafecard account do not match the details of ' .
                        'this account. Please check the first names, ' .
                        'surnames and dates of birth entered in both ' .
                        'accounts and request the payout again.';
                case 3167:
                case 3170:
                case 3194:
                case 3168:
                case 3230:
                case 3231:
                case 3232:
                case 3233:
                case 3234:
                    return 'Unfortunately, the payout could not be completed ' .
                        'due to a problem which has arisen with your my ' .
                        'paysafecard account. paysafecard has already sent ' .
                        'you an e-mail with further information on this. ' .
                        'Please follow the instructions found in this ' .
                        'e-mail before requesting the payout again.';
                case 3197:
                case 3198:
                    return 'Unfortunately, the payout could not be completed ' .
                        'due to a problem which has arisen with your my ' .
                        'paysafecard account. Please contact the ' .
                        'paysafecard support team. info@paysafecard.com';
                default:
                    return 'Unfortunately there has been a technical problem ' .
                        'and your payout request could not be executed. ' .
                        'If the problem persists, please contact our ' .
                        'customer support: support@company.com';
            }
        }

        return "Unknown error. HTTP info :" . print_r($http_info, true);
    }

    /**
     * @param bool $getListCurr
     * @param string $ps
     * @return array
     */
    public static function getSupportedCurrencies(bool $getListCurr = true, $ps = 'paysafecard'): array
    {
        $ApiFields = static::getApiFields($getListCurr);
        $fields = $ApiFields[$ps];

        return [
            'ARS' => [
                $ps => [
                    'name' => 'Argentine Peso',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'AUD' => [
                $ps => [
                    'name' => 'Australian Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'AZN' => [
                $ps => [
                    'name' => 'Azerbaijanian Manat',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BHD' => [
                $ps => [
                    'name' => 'Bahraini Dinar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BYR' => [
                $ps => [
                    'name' => 'Belarusian Ruble',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BOB' => [
                $ps => [
                    'name' => 'Bolivian Boliviano',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BAM' => [
                $ps => [
                    'name' => 'Bosnia and Herzegovina Convertible Mark',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BRL' => [
                $ps => [
                    'name' => 'Brazilian Real',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'BGN' => [
                $ps => [
                    'name' => 'Bulgarian Lev',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CAD' => [
                $ps => [
                    'name' => 'Canadian Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CLP' => [
                $ps => [
                    'name' => 'Chilean Peso',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CNY' => [
                $ps => [
                    'name' => 'China Yuan Renminbi',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'COP' => [
                $ps => [
                    'name' => 'Columbian Peso',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CRC' => [
                $ps => [
                    'name' => 'Costa Rican Colon',
                    'fields' => $fields,
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
            'CZK' => [
                $ps => [
                    'name' => 'Czech Koruna',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'DKK' => [
                $ps => [
                    'name' => 'Danish Krone',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'DOP' => [
                $ps => [
                    'name' => 'Dominican Peso',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'XCD' => [
                $ps => [
                    'name' => 'East Caribbean Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'EGP' => [
                $ps => [
                    'name' => 'Egyptian Pound',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'ETB' => [
                $ps => [
                    'name' => 'Ethiopian Birr',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'EUR' => [
                $ps => [
                    'name' => 'Euro',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'FJD' => [
                $ps => [
                    'name' => 'Fiji Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'GEL' => [
                $ps => [
                    'name' => 'Georgian Lari',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'GTQ' => [
                $ps => [
                    'name' => 'Guatemala Quetzal',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'HTG' => [
                $ps => [
                    'name' => 'Haiti Goude',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'HNL' => [
                $ps => [
                    'name' => 'Honduran Lempira',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'HKD' => [
                $ps => [
                    'name' => 'Hong Kong Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'HUF' => [
                $ps => [
                    'name' => 'Hungarian Forint',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'ISK' => [
                $ps => [
                    'name' => 'Iceland Krona',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'INR' => [
                $ps => [
                    'name' => 'Indian Rupee',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'IDR' => [
                $ps => [
                    'name' => 'Indonesia Rupiah',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'IRR' => [
                $ps => [
                    'name' => 'Iranian Rial',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'JMD' => [
                $ps => [
                    'name' => 'Jamaican Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'JPY' => [
                $ps => [
                    'name' => 'Japanese Yen',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'JOD' => [
                $ps => [
                    'name' => 'Jordanian Dinar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'KZT' => [
                $ps => [
                    'name' => 'Kazakhstan Tenge',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'KES' => [
                $ps => [
                    'name' => 'Kenyan Shilling',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'KRW' => [
                $ps => [
                    'name' => 'Korean Won',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'KWD' => [
                $ps => [
                    'name' => 'Kuwaiti Dinar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'LVL' => [
                $ps => [
                    'name' => 'Latvian Lats',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'LBP' => [
                $ps => [
                    'name' => 'Lebanese Pound',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'LYD' => [
                $ps => [
                    'name' => 'Libyan Dinars',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'LTL' => [
                $ps => [
                    'name' => 'Lithuanian Litas',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MWK' => [
                $ps => [
                    'name' => 'Malawi Kwacha',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MYR' => [
                $ps => [
                    'name' => 'Malaysian Ringgit',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MUR' => [
                $ps => [
                    'name' => 'Mauritius Rupee',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MXN' => [
                $ps => [
                    'name' => 'Mexican Peso',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MDL' => [
                $ps => [
                    'name' => 'Moldovan Leu',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'MAD' => [
                $ps => [
                    'name' => 'Moroccan Dirham',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'ILS' => [
                $ps => [
                    'name' => 'New Israeli Shekel',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'NZD' => [
                $ps => [
                    'name' => 'New Zealand Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'NGN' => [
                $ps => [
                    'name' => 'Nigerian Naira',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'NOK' => [
                $ps => [
                    'name' => 'Norwegian Kroner',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'OMR' => [
                $ps => [
                    'name' => 'Omani Rial',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PKR' => [
                $ps => [
                    'name' => 'Pakistan Rupee',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PAB' => [
                $ps => [
                    'name' => 'Panamanian Balboa',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PYG' => [
                $ps => [
                    'name' => 'Paraguayan Guarani',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PEN' => [
                $ps => [
                    'name' => 'Peruvian Sol',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PHP' => [
                $ps => [
                    'name' => 'Philippine Peso',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'PLN' => [
                $ps => [
                    'name' => 'Polish Zloty',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'GBP' => [
                $ps => [
                    'name' => 'British Pound Sterling',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'QAR' => [
                $ps => [
                    'name' => 'Qatari Rial',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'RON' => [
                $ps => [
                    'name' => 'Romanian New Leu',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'RUB' => [
                $ps => [
                    'name' => 'Russian Ruble',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'SAR' => [
                $ps => [
                    'name' => 'Saudi Arabian Riyal',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'RSD' => [
                $ps => [
                    'name' => 'Serbian Dinar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'SGD' => [
                $ps => [
                    'name' => 'Singapore Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'ZAR' => [
                $ps => [
                    'name' => 'South African Rand',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'LKR' => [
                $ps => [
                    'name' => 'Sri Lanka Rupee',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'SEK' => [
                $ps => [
                    'name' => 'Swedish Krona',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'CHF' => [
                $ps => [
                    'name' => 'Swiss Franc',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'SYP' => [
                $ps => [
                    'name' => 'Syrian Pound',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'TWD' => [
                $ps => [
                    'name' => 'Taiwan New Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'THB' => [
                $ps => [
                    'name' => 'Thai Baht',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'TTD' => [
                $ps => [
                    'name' => 'Trinidad and Tobago Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'TND' => [
                $ps => [
                    'name' => 'Tunisia Dinar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'TRY' => [
                $ps => [
                    'name' => 'Turkish Lira',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'UAH' => [
                $ps => [
                    'name' => 'Ukranian Hryunia',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'AED' => [
                $ps => [
                    'name' => 'UAE Dirham',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'UYU' => [
                $ps => [
                    'name' => 'Uruguay Peso',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'USD' => [
                $ps => [
                    'name' => 'United States Dollar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'VEF' => [
                $ps => [
                    'name' => 'Venezuelan Bolivar',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
            'VND' => [
                $ps => [
                    'name' => 'Viet Nam Dong',
                    'fields' => $fields,
                    self::WAY_DEPOSIT => true,
                    self::WAY_WITHDRAW => true,
                ],
            ],
        ];
    }
}
