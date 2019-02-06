<?php

namespace pps\qiwi;

use api\classes\ApiError;
use pps\querybuilder\QueryBuilder;
use Yii;
use yii\db\Exception;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * Class Qiwi
 * @package pps\qiwi
 */
class Qiwi extends Payment
{
    const API_URL = 'https://pay-qiwi.com/api/qiwi/';
    const QIWI_URL = 'https://qiwi.com/payment/form/';

    /** @var string */
    private $_api_token;
    /** @var string */
    private $_api_key;
    /** @var int */
    private $_query_errno;
    /** @var array */
    private $_info;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     * @throws InvalidParamException
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['api_token'])) {
            throw new InvalidParamException('api_token empty');
        }

        if (empty($data['api_key'])) {
            throw new InvalidParamException('api_key empty');
        }

        $this->_api_token = $data['api_token'];
        $this->_api_key = $data['api_key'];
    }

    /**
     * @param array $data
     * @return array
     */
    public function preInvoice(array $data): array
    {
        $validate = self::_validateTransaction($data['currency'], $data['payment_method'], self::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $fields = static::_getFields($data['currency'], $data['payment_method'], self::WAY_DEPOSIT);

        return [
            'data' => [
                'fields' => $fields,
                'currency' => $data['currency'],
                'amount' => $data['amount'],
                'buyer_write_off' => null,
                'merchant_refund' => $data['amount'],
            ]
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    public function invoice(array $data): array
    {
        $transaction = $data['transaction'];
        $requests = $data['requests'];

        $validate = self::_validateTransaction($transaction->currency, $transaction->payment_method, self::WAY_DEPOSIT);

        if ($validate !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            $this->logger->log($transaction->id, 100, $validate);

            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $requisites = json_decode($transaction->requisites, true);

        $message = self::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_DEPOSIT);

        if ($message !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            $this->logger->log($transaction->id, 100, $message);

            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $transaction->trn_process_mode = self::MODE_CHANGED_AMOUNT;

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $query = [
            'sum' => $transaction->amount,
            'wallet' => $requisites['phone']
        ];

        if ($transaction->commission_payer == self::COMMISSION_BUYER) {
            $query['include_fee'] = 0;
        }

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $result = $this->_query('orderPayIn', $query);

        $transaction->result_data = json_encode($result);

        $this->logger->log($transaction->id, 3, $transaction->result_data);

        Yii::info(['query' => $query, 'result' => $result], 'payment-qiwi-invoice');

        $answer = [];

        if (isset($result['status']) && $result['status'] === 'done') {

            $transaction->external_id = $result['ticket']['ticket'];
            $transaction->write_off = $result['ticket']['sum'];
            $transaction->refund = $result['ticket']['amount'];
            $transaction->save(false);

            $params = [
                'amountInteger' => floor($transaction->write_off),
                'amountFraction' => round(($transaction->write_off - floor($transaction->write_off)) * 100)
            ];

            $params['currency'] = 643; // RUB
            $params["extra['comment']"] = $result['ticket']['ticket'];
            $params["extra['account']"] = $result['ticket']['wallet'];

            $providerId = self::_getProviderId($transaction->currency, $transaction->payment_method);

            $answer['redirect'] = [
                'method' => 'GET',
                'url' => self::QIWI_URL . $providerId,
                'params' => $params,
            ];

            $answer['data'] = $transaction::getDepositAnswer($transaction);
            $data['updateStatusJob']->transaction_id = $transaction->id;

        } else if ($this->_query_errno != CURLE_OK) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer['status'] = 'error';
            $answer['message'] = self::ERROR_NETWORK;

        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer['status'] = 'error';

            if (isset($result['message'])) {
                $answer['message'] = $result['message'];
            } else {
                $answer['message'] = self::ERROR_OCCURRED;
            }

            $this->logger->log($transaction->id, 100, $answer);
        }

        return $answer;
    }

    /**
     * @param $currency
     * @param $method
     * @return bool
     */
    private static function _getProviderId($currency, $method)
    {
        $currencies = self::getSupportedCurrencies();

        return $currencies[$currency][$method]['provider_id'] ?? false;
    }

    /**
     * Method for receiving and updating statuses
     * @param array $data
     * @return bool
     */
    public function receive(array $data): bool
    {
        return true;
    }

    /**
     * @param array $params
     * @return array
     */
    public function preWithDraw(array $params)
    {
        $validate = self::_validateTransaction($params['currency'], $params['payment_method'], self::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $fields = self::_getFields($params['currency'], $params['payment_method'], self::WAY_WITHDRAW);

        return [
            'data' => [
                'fields' => $fields,
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => null,
                'buyer_receive' => null,
            ]
        ];
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function withDraw(array $params)
    {
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        $validate = self::_validateTransaction($transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW);

        if ($validate !== true) {
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

        $requisites = json_decode($transaction->requisites, true);

        $message = self::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW);

        if ($message !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return [
                'status' => 'error',
                'message' => $message,
                'code' => ApiError::REQUISITE_FIELD_NOT_FOUND
            ];
        }

        $query = [
            'sum' => $transaction->amount
        ];

        if ($transaction->commission_payer == self::COMMISSION_MERCHANT) {
            $query['include_fee'] = 0;
        }

        if ($transaction->payment_method == 'card') {
            $query['type'] = 'card';
            $query['wallet'] = $requisites['number'];
        } else {
            $query['wallet'] = $requisites['phone'];
        }

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $withdrawResponse = $this->_query('orderPayOut', $query);

        $transaction->result_data = json_encode($withdrawResponse);

        $this->logger->log($transaction->id, 3, $transaction->result_data);

        Yii::info(['query' => $query, 'result' => $withdrawResponse, 'info' => $this->_info], 'payment-qiwi-withdraw');

        $answer = [];

        if (isset($withdrawResponse['status']) && $withdrawResponse['status'] === 'done') {

            $transaction->external_id = $withdrawResponse['ticket']['ticket'];
            $transaction->write_off = $withdrawResponse['ticket']['amount'];
            $transaction->receive = $withdrawResponse['ticket']['sum'];
            $transaction->save(false);

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

            $params['updateStatusJob']->transaction_id = $transaction->id;

        } else if ($this->_query_errno == CURLE_OPERATION_TIMEOUTED) {
            $transaction->status = self::STATUS_TIMEOUT;
            $transaction->save(false);
            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else if ($this->_query_errno != CURLE_OK) {
            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->result_data = $this->_query_errno;
            $transaction->save(false);
            $params['updateStatusJob']->transaction_id = $transaction->id;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer['status'] = 'error';

            if (isset($withdrawResponse['message'])) {
                $answer['message'] = $withdrawResponse['message'];
            } else {
                $answer['message'] = self::ERROR_OCCURRED;
            }

            $this->logger->log($transaction->id, 100, $answer);
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

            $query = [
                'ticket' => $transaction->external_id
            ];

            $response = $this->_query('checkPayment', $query);

            Yii::info(['query' => $query, 'res' => $response], 'payment-qiwi-status');

            $this->logger->log($transaction->id, 8, $response);

            if (isset($response['status']) && $response['status'] === 'done') {
                if (isset($response['ticket_status'])) {
                    self::_setStatus($transaction, $response['ticket_status']);

                    if ($transaction->way == self::WAY_DEPOSIT) {
                        if (isset($response['sum'])) {
                            if ($transaction->commission_payer == self::COMMISSION_MERCHANT) {
                                $transaction->amount = $response['sum'];
                            }
                            if ($transaction->commission_payer == self::COMMISSION_BUYER) {
                                $transaction->amount = $response['sum'] - $response['fee'];
                            }
                            $transaction->refund = round($response['sum'] - $response['fee'], 2);
                            //$transaction->amount = $response['sum'];
                            $transaction->write_off = $response['sum'];
                        }
                    }

                    if ($transaction->way == self::WAY_WITHDRAW) {
                        if (isset($response['sum'])) {
                            $transaction->receive = $response['sum'];
                        }
                    }

                    $transaction->save(false);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        return md5(json_encode($dataSet) . $this->_api_key);
    }

    /**
     * Main query method
     * @param string $path
     * @param array $params
     * @param bool $post
     * @return mixed
     */
    private function _query(string $path, array $params = [], $post = true)
    {
        $url = self::API_URL . trim($path, '/');

        $request = (new QueryBuilder($url))
            ->setParams($params)
            ->setHeader('Auth', $this->_api_token)
            ->setHeader('Sign', $this->_getSign($params))
            ->setOptions([
                CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
                CURLOPT_TIMEOUT => self::$timeout,
            ]);

        if ($post) {
            $request->asPost();
            $request->json();
        }

        $result = $request->send();

        $this->_query_errno = $result->getErrno();
        $this->_info = $result->getInfo();

        return $result->getResponse(true);
    }

    /**
     * Get supported currencies and methods
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        return require(__DIR__ . '/currency_lib.php');
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
     * @param array $data
     * @return array
     */
    public static function getTransactionQuery(array $data): array
    {
        return [];
    }

    /**
     * Get transaction id.
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return 0;
    }

    /**
     * Get success answer for stopping send callback data
     * @return string
     */
    public static function getSuccessAnswer()
    {
        return '';
    }

    /**
     * Get response format for success answer
     * @return string
     */
    public static function getResponseFormat()
    {
        return '';
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
     * Validate transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return bool|string
     */
    private static function _validateTransaction(string $currency, string $method, string $way)
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

        return true;
    }

    /**
     * @param $transaction
     * @param $status
     */
    private static function _setStatus($transaction, $status)
    {
        switch ($status) {
            case 'received':
                $transaction->status = self::STATUS_SUCCESS;
                break;
            case 'cancelled':
                $transaction->status = self::STATUS_CANCEL;
                break;
            case 'waiting':
                //$transaction->status = self::STATUS_PENDING;
                break;
        }
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


    /**
     * Get fields for filling
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
}