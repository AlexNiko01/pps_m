<?php

namespace pps\interkassa;

use common\models\Transaction;
use api\classes\ApiError;
use Yii;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * Class Interkassa
 * @package pps\interkassa
 */
class Interkassa extends Payment
{
    const API_URL = 'https://api.interkassa.com/v1/';
    const SCI_URL = 'https://sci.interkassa.com/';

    protected static $methods = ['inputWays', 'outputWays', 'clearCache'];

    /** @var string */
    private $_user_id;
    /** @var string */
    private $_api_key;
    /** @var string */
    private $_shop_id;
    /** @var string */
    private $_secret_key;
    /** @var array */
    private $_allow_ip;
    /** @var int */
    private $_errno;
    /** @var int */
    private $_delayTimeForJob = 60 * 60 * 2;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (!$data['user_id']) {
            throw new InvalidParamException('user_id empty');
        }
        if (!$data['api_key']) {
            throw new InvalidParamException('api_key empty');
        }
        if (!$data['shop_id']) {
            throw new InvalidParamException('shop_id empty');
        }
        if (!$data['secret_key']) {
            throw new InvalidParamException('secret_key empty');
        }

        $this->_user_id = $data['user_id'];
        $this->_api_key = $data['api_key'];
        $this->_shop_id = $data['shop_id'];
        $this->_secret_key = $data['secret_key'];

        $this->_allow_ip = [
            '151.80.190.97',
            '151.80.190.98',
            '151.80.190.99',
            '151.80.190.100',
            '151.80.190.101',
            '151.80.190.102',
            '151.80.190.103',
            '151.80.190.104',
            '5.9.49.227'
        ];

        if (YII_ENV === 'dev') $this->_allow_ip[] = '127.0.0.1';
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        $answer = [];

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = self::validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_DEPOSIT);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $query = [
            'ik_co_id' => $this->_shop_id,
            'ik_pm_no' => 2,
            'ik_desc' => 'preInvoice',
            'ik_am' => $params['amount'],
            'ik_int' => 'json',
            'ik_cur' => $params['currency'],
            'ik_act' => 'payways_calc',
            'ik_pw_via' => $this->_getCurrencyAlias($params['currency'], $params['payment_method'], self::WAY_DEPOSIT),
        ];

        $query['ik_sign'] = $this->_getSign($query);

        $result = $this->_query(self::SCI_URL, $query, true);

        if (isset($result['resultMsg']) && $result['resultMsg'] === 'Success') {
            if (isset($result['resultData']) && isset($result['resultData']['invoice'])) {

                $answer = [
                    'data' => [
                        'fields' => [],
                        'amount' => $params['amount'],
                        'buyer_write_off' => $result['resultData']['invoice']['psPrice'],
                        'merchant_refund' => $result['resultData']['invoice']['coAmount'],
                        'currency' => $params['currency'],
                    ]
                ];
            }
        } else {

            $answer = [
                'status' => 'error',
                'message' => $result['resultMsg'] ?? self::ERROR_OCCURRED
            ];
        }

        return $answer;
    }

    /**
     * Invoicing for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        /**
         * @var $transaction Transaction
         */
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }

        if (!empty($transaction->commission_payer)) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $this->logger->log($transaction->id, 100, self::ERROR_COMMISSION_PAYER);

            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $validate = self::validateTransaction($transaction->currency, $transaction->payment_method, $transaction->amount, self::WAY_DEPOSIT);

        if ($validate !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $this->logger->log($transaction->id, 100, self::ERROR_COMMISSION_PAYER);

            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $answer = [];

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $dispatcher = new Dispatcher(self::getSupportedCurrencies());

        $payway = $dispatcher->getDepositId($transaction->currency, $transaction->payment_method, $params['user_currencies']);

        if ($payway === false) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $error = "Method '{$transaction->payment_method}' is not supported!";

            $this->logger->log($transaction->id, 100, $error);

            return [
                'status' => 'error',
                'message' => $error
            ];
        }

        if ($payway === null) {
            $this->logger->log($transaction->id, 100, [
                'message' => "Payway not found",
                'data' => [
                    'currency' => $transaction->currency,
                    'method' => $transaction->payment_method,
                    'user_currencies' => $params['user_currencies']
                ]
            ]);

            return [
                'status' => 'error',
                'message' => "Something went wrong!"
            ];
        }

        $query = [
            'ik_co_id' => $this->_shop_id,
            'ik_pm_no' => $transaction->id,
            'ik_am' => $transaction->amount,
            'ik_desc' => $transaction->comment,
            'ik_int' => 'web',
            'ik_act' => 'process',
            /*'ik_pw_via' => $this->_getCurrencyAlias(
                $transaction->currency,
                $transaction->payment_method,
                $transaction->way
            ),*/
            'ik_pw_via' => $payway,
            'ik_cur' => $transaction->currency,
            'ik_ltm' => 3600,
            'ik_ia_u' => $params['callback_url'],
            'ik_ia_m' => 'POST',
            'ik_suc_u' => $params['success_url'],
            'ik_suc_m' => 'GET',
            'ik_fal_u' => $params['fail_url'],
            'ik_fal_m' => 'GET',
        ];

        $query['ik_sign'] = $this->_getSign($query);

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);
        $this->logger->log($transaction->id, 3, $transaction->result_data);

        $transaction->save(false);

        $answer['redirect'] = [
            'method' => 'POST',
            'url' => self::SCI_URL,
            'params' => $query
        ];

        $answer['data'] = $transaction::getDepositAnswer($transaction);

        Yii::info(['query' => $query, 'result' => []], 'payment-interkassa-invoice');

        return $answer;
    }

    /**
     * Method for receiving and updating statuses
     * @param array $data
     * @return bool
     */
    public function receive(array $data): bool
    {
        /**
         * @var $transaction Transaction
         */
        $transaction = $data['transaction'];
        $receiveData = $data['receive_data'];

        if (in_array($transaction->status, self::getFinalStatuses())) {
            return true;
        }

        $need = ['ik_inv_st', 'ik_co_id', 'ik_ps_price', 'ik_cur'];

        if (!self::checkRequiredParams($need, $receiveData)) {
            Yii::error([
                'receive_data' => $receiveData,
                'need' => $need
            ], 'payment-freekassa-receive');
            return false;
        }

        if (!$this->_checkIP()) {
            $this->logAndDie(
                'Interkassa receive() from undefined server',
                'IP = ' . Yii::$app->request->getUserIP(),
                "Undefined server\n"
            );
        }

        if (!$this->_checkReceivedSign($receiveData)) {
            return false;
        }

        if ($this->_shop_id != $receiveData['ik_co_id']) {
            $this->logAndDie(
                'Interkassa receive() user_id not equal received user_id',
                "user_id = {$this->_shop_id}\nreceived user_id = {$receiveData['ik_co_id']}",
                "Transaction user_id not equal received user_id\n",
                'interkassa-receive'
            );
        }

        $receiveAmount = (float)$receiveData['ik_am'];

        if ((float)$transaction->amount != $receiveAmount) {
            $this->logAndDie(
                'Interkassa receive() transaction amount not equal received amount',
                "Transaction amount = {$transaction->amount}\nreceived amount = {$receiveAmount}",
                "Transaction amount not equal received amount\n",
                'interkassa-receive'
            );
        }

        if (strtolower($data['currency']) != strtolower($receiveData['ik_cur'])) {
            $this->logAndDie(
                'Interkassa receive() different currency',
                'Casino currency = ' . $data['currency'] . "\nreceived currency = " . $receiveData['CUR_ID'],
                "Different currency\n",
                'interkassa-receive'
            );
        }

        if (empty($transaction->write_off) && isset($receiveData['ik_ps_price'])) {
            $transaction->write_off = $receiveData['ik_ps_price'];
        }

        if (empty($transaction->refund) && isset($receiveData['ik_co_rfn'])) {
            $transaction->refund = $receiveData['ik_co_rfn'];
        }

        if (empty($transaction->external_id) && isset($receiveData['ik_inv_id'])) {
            $transaction->external_id = $receiveData['ik_inv_id'];
        }

        if (strtolower($receiveData['ik_inv_st']) === 'success') {
            $transaction->status = self::STATUS_SUCCESS;
        } else if (strtolower($receiveData['ik_inv_st']) === 'fail') {
            $transaction->status = self::STATUS_ERROR;
        }

        $transaction->save(false);

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
        $dispatcher = new Dispatcher(self::getSupportedCurrencies());
        //return $dispatcher->getGroups();

        try {
            $payway = $dispatcher->getWithdrawId($params['currency'], $params['payment_method'], $params['user_currencies']);
            $originMethod = $dispatcher->findWithdrawOriginMethod($params['currency'], $params['payment_method'], $params['user_currencies']);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => "Can't find alias of method"
            ];
        }

        //return [$params['currency'], $params['payment_method'], $originMethod, $payway];

        if ($payway === null) {
            return [
                'status' => 'error',
                'message' => "Something went wrong!"
            ];
        }

        if ($payway === false) {
            return [
                'status' => 'error',
                'message' => "Method '{$params['payment_method']}' is not supported!"
            ];
        }

        $validate = self::validateTransaction($params['currency'], $originMethod, $params['amount'], self::WAY_WITHDRAW);

        if ($validate !== true) {
            return [
                'status' => 'error',
                'message' => $validate,
                'code' => self::$error_code
            ];
        }

        $fields = self::_getRequiredFields($params['currency'], $originMethod);

        $answer = [
            'data' => [
                'fields' => $fields,
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => null,
                'buyer_receive' => null,
            ]
        ];

        if ($commission = $this->_getCommission($params['amount'], $params['currency'], $params['payment_method'])) {
            if ($params['commission_payer'] === self::COMMISSION_BUYER) {
                $answer['data']['buyer_receive'] = $params['amount'] - $commission;
            }

            if ($params['commission_payer'] === self::COMMISSION_MERCHANT) {
                $answer['data']['buyer_receive'] = $params['amount'];
            }
        }

        return $answer;
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    public function withDraw(array $params)
    {
        /**
         * @var $transaction Transaction
         */
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        $dispatcher = new Dispatcher(self::getSupportedCurrencies());

        try {
            $payway = $dispatcher->getWithdrawId($transaction->currency, $transaction->payment_method, $params['user_currencies']);
            $originMethod = $dispatcher->findWithdrawOriginMethod($transaction->currency, $transaction->payment_method, $params['user_currencies']);
        } catch (\Exception $e) {
            $mess = $e->getMessage() . "\n" .  $e->getTraceAsString();
            $this->logger->log($transaction->id, 100, $mess);
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            return [
                'status' => 'error',
                'message' => "Can't find alias of method '{$transaction->payment_method}'"
            ];
        }

        /*try {
            $transaction->save(false);
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => self::ERROR_TRANSACTION_ID
            ];
        }*/

        if ($payway === null) {
            $transaction->status = self::STATUS_CANCEL;
            $transaction->save(false);

            $this->logger->log($transaction->id, 100, [
                'message' => "Payway not found",
                'data' => [
                    'currency' => $transaction->currency,
                    'method' => $transaction->payment_method
                ]
            ]);

            return [
                'status' => 'error',
                'message' => "Something went wrong!"
            ];
        }

        if ($payway === false) {
            $transaction->status = self::STATUS_CANCEL;
            $transaction->save(false);

            Yii::error([
                'message' => "Payway is not supported!",
                'data' => [
                    'currency' => $transaction->currency,
                    'method' => $transaction->payment_method
                ]
            ], 'payment-interkassa-withdraw');

            return [
                'status' => 'error',
                'message' => "Method '{$transaction->payment_method}' is not supported!"
            ];
        }

        $validate = self::validateTransaction($transaction->currency, $originMethod, $transaction->amount, self::WAY_WITHDRAW);

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

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $query = [
            'paymentNo' => $transaction->id,
            'amount' => $transaction->amount,
            //'paywayId' => self::_getCurrencyID($transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW),
            'paywayId' => $payway,
            'calcKey' => 'ikPayerPrice',
            'action' => 'process'
        ];

        if ($transaction->commission_payer === self::COMMISSION_BUYER) {
            $query['calcKey'] = 'ikPayerPrice';
        }

        if ($transaction->commission_payer === self::COMMISSION_MERCHANT) {
            $query['calcKey'] = 'psPayeeAmount';
        }

        $requisites = json_decode($transaction->requisites, true);
        $message = self::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW);

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

        $purse = $this->_getPurse($transaction->currency);

        if (!$purse) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $this->logger->log($transaction->id, 100, 'Purse not found!');

            return [
                'status' => 'error',
                'message' => 'Purse not found!'
            ];
        }

        if ($purse['balance'] < $transaction->amount) {
            $transaction->status = self::STATUS_CANCEL;
            $transaction->save(false);

            $this->logger->log($transaction->id, 100, 'Purse balance is too low!');

            return [
                'status' => 'error',
                'message' => 'Purse balance is too low!'
            ];
        }

        $query['purseId'] = $purse['id'];

        if (!empty($requisites)) {
            foreach ($requisites as $key => $fields) {
                $requisites[$key] = urldecode($fields);
            }
            $query['details'] = $requisites;
        }

        $transaction->query_data = json_encode($query);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $result = $this->_query(self::API_URL . 'withdraw', $query, true, true);

        $transaction->result_data = json_encode($result);

        $this->logger->log($transaction->id, 3, $transaction->result_data);

        Yii::info(['query' => $query, 'result' => $result], 'payment-interkassa-withdraw');

        if (isset($result['status']) && $result['status'] === 'ok' && $result['code'] == '0') {

            $transaction->external_id = $result['data']['withdraw']['id'];
            $transaction->receive = $result['data']['withdraw']['psAmount'];
            $transaction->write_off = $result['data']['withdraw']['payeeReceive'];
            $transaction->status = self::STATUS_CREATED;
            $transaction->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;
            $params['updateStatusJob']->delay_time = $this->_delayTimeForJob;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else if ($this->_errno == CURLE_OPERATION_TIMEOUTED) {
            $transaction->status = self::STATUS_TIMEOUT;
            $transaction->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;
            $params['updateStatusJob']->delay_time = $this->_delayTimeForJob;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else if ($this->_errno != CURLE_OK) {
            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->result_data = $this->_errno;
            $transaction->save(false);

            $params['updateStatusJob']->transaction_id = $transaction->id;
            $params['updateStatusJob']->transaction_id = $this->_delayTimeForJob;

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer = [
                'status' => 'error',
                'message' => $result['message'] ?? self::ERROR_OCCURRED
            ];

            $this->logger->log($transaction->id, 100, $answer);
        }

        return $answer;
    }

    /**
     * @param object $transaction
     * @param null $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if (in_array($transaction->status, self::getNotFinalStatuses())) {

            if ($transaction->way === self::WAY_DEPOSIT) {

                if (!$transaction->external_id) {
                    if (!$result = $this->_getExternalInvoiceInfo($transaction->id)) {
                        return false;
                    }
                    $transaction->external_id = $result['id'];
                } else {
                    $result = $this->_query(self::API_URL . "co-invoice/{$transaction->external_id}", [], false, true, $this->_getAccountIDHeaders());

                    if (isset($result['status']) && $result['status'] === 'ok' && $result['code'] == '0') {
                        $result = $result['data'];
                    } else {
                        if (!$result = $this->_getExternalInvoiceInfo($transaction->id)) {
                            return false;
                        }
                        $transaction->external_id = $result['id'];
                    }
                }

                if (self::_isFinalDeposit($result['state'])) {

                    if (self::_isSuccessDeposit($result['state'])) {
                        $transaction->status = self::STATUS_SUCCESS;
                    } else {
                        $transaction->status = self::STATUS_ERROR;
                    }
                } else {
                    $transaction->status = self::STATUS_PENDING;
                }

                $transaction->save(false);

                $this->logger->log($transaction->id, 8, $result);

                return true;
            }

            if ($transaction->way === self::WAY_WITHDRAW) {

                if (empty($transaction->external_id)) {
                    $url = self::API_URL . 'withdraw/?paymentNo-' . $transaction->id;
                } else {
                    $url = self::API_URL . 'withdraw/' . $transaction->external_id;
                }

                $result = $this->_query($url, [], false, true, $this->_getAccountIDHeaders());

                if (isset($result['status']) && $result['status'] === 'ok' && $result['code'] == '0') {
                    if (self::_isFinalWithdraw($result['data']['state'])) {

                        if (self::_isSuccessWithdraw($result['data']['state'])) {
                            $transaction->status = self::STATUS_SUCCESS;
                        } else {
                            $transaction->status = self::STATUS_ERROR;
                        }
                    } else {
                        $transaction->status = self::STATUS_PENDING;
                    }
                }

                $this->logger->log($transaction->id, 8, $result);

                $transaction->save(false);

                return true;
            }


        }

        return false;
    }

    /**
     * @param $data
     * @return string
     */
    public function inputWaysMethod($data)
    {
        $result = $this->_query(self::API_URL . 'checkout', [], false, true);

        if (isset($result['status']) && $result['status'] == 'ok') {
            $inputPayways = $result['data'][$this->_shop_id]['paysystemInputPayways'] ?? [];
            if (empty($inputPayways)) {
                return '<h2 style="text-align: center;">Can not find methods!</h2>';
            } else {
                $html = '<h3 style="text-align: center;">Input payways</h3>';
                foreach ($inputPayways as $code => $payway) {
                    $html .= '<div id="ways">';
                    $html .= "\n<p data-id='{$code}'><b>id</b>: {$code}, <b>alias</b>: {$payway}</p><hr>";
                    $html .= '</div>';
                }
                $html .= '<button type="button" onclick="enableWays()">Enable all deposit ways</button>';

                $script = <<<SCRIPT
<script>
function enableWays() {
    let ways = document.querySelectorAll('#ways > p');
    
    ways.forEach(function(way) {
        let elem = $('b:contains("' + way.getAttribute('data-id') + '")');
        if (elem) {
            elem.parent().find('input').attr('checked', true);
        }
    });
}
</script>
SCRIPT;
                $html .= $script;


                return $html;
            }
        } else {
            return '<h2 style="text-align: center;">Error</h2>';
        }
    }

    /**
     * @param $data
     * @return string
     */
    public function outputWaysMethod($data)
    {
        $purse = $this->_getPurse('UAH');

        $result = $this->_query(self::API_URL . 'paysystem-output-payway', [
            'purseId' => $purse['id']
        ], false, true);

        if (isset($result['status']) && $result['status'] == 'ok') {
            $outputPayways = $result['data'] ?? [];
            if (empty($outputPayways)) {
                return '<h2 style="text-align: center;">Can not find methods!</h2>';
            } else {
                $html = '<h3 style="text-align: center;">Input payways</h3>';
                foreach ($outputPayways as $code => $payway) {
                    $html .= '<div id="ways">';
                    $html .= "\n<p data-id='{$code}'><b>id</b>: {$code}, <b>alias</b>: {$payway['als']}</p><hr>";
                    $html .= '</div>';
                }
                $html .= '<button type="button" onclick="enableWays()">Enable all withdraw ways</button>';

                $script = <<<SCRIPT
<script>
function enableWays() {
    let ways = document.querySelectorAll('#ways > p');
    
    ways.forEach(function(way) {
        let elem = $('b:contains("' + way.getAttribute('data-id') + '")');
        if (elem) {
            elem.parent().find('input').attr('checked', true);
        }
    });
}
</script>
SCRIPT;
                $html .= $script;


                return $html;
            }
        } else {
            return '<h2 style="text-align: center;">Error</h2>';
        }
    }

    /**
     * @param $data
     * @return string
     */
    public function clearCacheMethod($data)
    {
        $cache = Yii::$app->cache;

        $cache->delete([__CLASS__, 'currency_list']);
        $cache->delete([__CLASS__, '_getCurrencyData', 'input']);
        $cache->delete([__CLASS__, '_getCurrencyData', 'output']);
        $cache->delete(Dispatcher::$keys_map_key);
        $cache->delete(Dispatcher::$groups_key);
        $cache->delete(Dispatcher::$converted_currencies_key);
        self::getSupportedCurrencies();
        self::getApiFields();

        return '<h3 style="text-align: center;">DONE</h3>';
    }

    /**
     * @param string $tr_id
     * @return bool|int|string
     */
    private function _getExternalInvoiceInfo(string $tr_id)
    {
        $result = $this->_query(self::API_URL . 'co-invoice', [], false, true, $this->_getAccountIDHeaders());

        if (isset($result['status']) && $result['status'] === 'ok' && $result['code'] == '0') {
            foreach ($result['data'] as $id => $invoice) {
                if ($invoice['paymentNo'] === $tr_id) {
                    return $invoice;
                }
            }
        }

        return false;
    }

    /**
     * Get Ik-Api-Account-Id header for using private API methods
     * @return array
     */
    private function _getAccountIDHeaders()
    {
        $key = [__CLASS__, __METHOD__, 'Ik-Api-Account-Id', $this->_api_key];

        return Yii::$app->cache->getOrSet($key, function () {
            $accountInfo = $this->_query(self::API_URL . 'account', [], false, true);

            if (isset($accountInfo['status']) && $accountInfo['status'] === 'ok' && $accountInfo['code'] == '0') {
                foreach ($accountInfo['data'] as $id => $account) {
                    if ($account['tp'] === 'b') {
                        return ["Ik-Api-Account-Id: $id"];
                    }
                }
            }

            return [];
        }, 3600);
    }

    /**
     * Search purse
     * @param string $currency
     * @return bool
     */
    private function _getPurse(string $currency)
    {
        $purses = $this->_query(self::API_URL . 'purse', [], false, true, $this->_getAccountIDHeaders());

        if (isset($purses['status']) && $purses['status'] === 'ok' && $purses['code'] == '0') {
            foreach ($purses['data'] as $id => $purse) {
                if (preg_match("/\s{$currency}$/i", $purse['name'])) {
                    return $purse;
                }
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
        $sign = $data['ik_sign'];
        unset($data['ik_sign']);
        $expectedSign = $this->_getSign($data);

        if ($sign != $expectedSign) {
            Yii::error("Interkassa receive() wrong ik_sign is received: expectedSign = {$expectedSign} \nSign = {$sign}", 'payment-interkassa-receive');
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
        return in_array($_SERVER['REMOTE_ADDR'] ?? '', $this->_allow_ip);
    }

    /**
     * Main query method
     * @param string $url
     * @param array $params
     * @param bool $post
     * @param bool $api
     * @param array $headers
     * @return bool|mixed
     */
    private function _query(string $url, array $params, $post = true, $api = false, $headers = [])
    {
        $query = http_build_query($params);

        if (!$post && count($params) > 0) {
            $url .= "?" . $query;
        }

        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => self::$connect_timeout,
            CURLOPT_TIMEOUT => self::$timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST => $post
        ];

        if ($post) {
            $options[CURLOPT_POSTFIELDS] = $query;
        }

        if ($api) {
            $headers[] = "Authorization: Basic " . base64_encode($this->_user_id . ':' . $this->_api_key);
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        //$info = curl_getinfo($ch);
        //$error = curl_error($ch);
        $this->_errno = curl_errno($ch);

        curl_close($ch);

        if ($response === false) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    /**
     * Sign function
     * @param array $dataSet
     * @return string
     */
    private function _getSign(array $dataSet): string
    {
        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $this->_secret_key);
        $signString = implode(':', $dataSet);

        return base64_encode(md5($signString, true));
    }

    /**
     * Get supported currencies
     * @return array
     */
    public static function getSupportedCurrencies(): array
    {
        $cache = Yii::$app->cache;

        return $cache->getOrSet([__CLASS__, 'currency_list'], function () {
            $inputs = self::_getCurrencyData('input');
            $outputs = self::_getCurrencyData('output');

            $currencies = [];

            foreach ($inputs as $id => $way) {
                $currUpper = self::_getCurrencyFromAls($way['als']);

                $method = self::_modifyMethod($way['ser']);

                $ps = [
                    'name' => "{$way['ps']}: {$method} <span class='hidden'>{$id}</span>",
                    'd_id' => $id,
                    'deposit' => true,
                    'fields' => [
                        'deposit' => []
                    ]
                ];

                if (isset($currencies[$currUpper]) && isset($currencies[$currUpper][$method])) {
                    $paymentSystems = array_filter($currencies[$currUpper], function ($item) use ($method) {
                        preg_match("~^($method)(-([0-9]+))?$~", $item, $matches);
                        return isset($matches[1]);
                        //return $item == $method;
                    }, ARRAY_FILTER_USE_KEY);

                    $number = count($paymentSystems) + 1;

                    $ps['name'] = "{$way['ps']}: {$method}-{$number} <span class='hidden'>{$id}</span> ";
                    $currencies[$currUpper][$method . '-' . $number] = $ps;

                } else {
                    $currencies[$currUpper][$method] = $ps;
                }
            }

            foreach ($outputs as $id => $way) {
                $currUpper = self::_getCurrencyFromAls($way['als']);

                $method = self::_modifyMethod($way['ser']);

                $ps = [
                    'name' => "{$way['ps']}: {$method} <span class='hidden'>{$id}</span>",
                    'w_id' => $id,
                    'withdraw' => true,
                    'fields' => [
                        'withdraw' => []
                    ]
                ];

                foreach ($way['prm'] ?? [] as $param) {
                    $ps['fields']['withdraw'][$param['al']] = [
                        'label' => strip_tags($param['tt']) ?? 'Label',
                        'regex' => $param['re'] ?? '',
                        'example' => $param['ex'] ?? ''
                    ];
                }

                if (isset($currencies[$currUpper]) && isset($currencies[$currUpper][$method])) {
                    $paymentSystems = array_filter($currencies[$currUpper], function ($item) use ($method) {
                        preg_match("~^($method)(-([0-9]+))?$~", $item, $matches);
                        return isset($matches[1]);
                    }, ARRAY_FILTER_USE_KEY);
                    $number = count($paymentSystems) + 1;

                    $ps['name'] = "{$way['ps']}: {$method}-{$number} <span class='hidden'>{$id}</span>";

                    $currencies[$currUpper][$method . '-' . $number] = $ps;
                } else {
                    $currencies[$currUpper][$method] = $ps;
                }
            }

            return $currencies;
        });
    }

    /**
     * @param $als
     * @return mixed|string
     */
    private static function _getCurrencyFromAls($als)
    {
        $alsExplode = explode('_', $als);
        $currency = end($alsExplode);
        $currUpper = strtoupper($currency);

        $associations = [
            'WME' => 'EUR',
            'WMB' => 'BYR',
            'WMZ' => 'USD',
            'WMR' => 'RUB',
            'WMU' => 'UAH',
            'WMK' => 'KZT',
        ];

        return in_array($currUpper, array_keys($associations)) ? $associations[$currUpper] : $currUpper;
    }

    /**
     * @param $method
     * @return mixed
     */
    private static function _modifyMethod($method)
    {
        $methods = [
            'advcash' => 'adv-cash',
            'yandexmoney' => 'yamoney',
            'qiwiterminal' => 'qiwi-terminal',
            'worldterminal' => 'world-terminal',
            'megafone' => 'megafon',
        ];

        return in_array($method, array_keys($methods)) ? $methods[$method] : $method;
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        $dispatcher = new Dispatcher(self::getSupportedCurrencies());
        $currencies = $dispatcher->getConvertedCurrencies();

        foreach ($currencies as $currency => $methods) {
            foreach ($methods as $key => $method) {
                if (isset($method['deposit']) && $method['deposit'] && !isset($method['fields']['deposit'])) {
                    $method['fields']['deposit'] = [];
                }
                $currencies[$currency][$key] = $method['fields'];
            }
        }

        return $currencies;
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
     * Validate transaction before send to payment system
     * @param string $currency
     * @param string $method
     * @param float $amount
     * @param string $way
     * @return bool|string
     */
    public static function validateTransaction(string $currency, string $method, float $amount, string $way)
    {
        $currencies = self::getSupportedCurrencies();

        if (!isset($currencies[$currency])) {
            return "Currency '{$currency}' does not supported";
        }

        if (!isset($currencies[$currency][$method])) {
            return "Method '{$method}' does not exist";
        }

        $currencyID = self::_getCurrencyID($currency, $method, $way);

        $methodArr = $currencies[$currency][$method];

        if (!isset($methodArr[$way]) || !$methodArr[$way]) {
            return "Payment system does not support '{$way}'";
        }

        $min = self::_getMinMax($currencyID, $way, 'min');
        $max = self::_getMinMax($currencyID, $way, 'max');

        if ($min && $max) {
            if ($min > $amount || $max <= $amount) {
                if ($min > $amount) self::$error_code = ApiError::PAYMENT_MIN_AMOUNT_ERROR;
                if ($max <= $amount) self::$error_code = ApiError::PAYMENT_MAX_AMOUNT_ERROR;
                return "Amount should to be more than '{$min}' and less than '{$max}'";
            }
        }

        if ($min) {
            if ($min > $amount) {
                return "Amount should to be more than '{$min}'";
            }
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
        return isset($data['ik_pm_no']) ? ['id' => $data['ik_pm_no']] : [];
    }

    /**
     * Getting transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    public static function getTransactionID(array $data)
    {
        return $data['ik_pm_no'] ?? 0;
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
     * @param string $method
     * @return mixed
     */
    private static function _queryGet(string $method)
    {
        $ch = curl_init(self::API_URL . $method);

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'
        ]);

        $response = curl_exec($ch);

        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Checking final status for deposit
     * @param int $status
     * @return boolean
     */
    private static function _isFinalDeposit(int $status): bool
    {
        $statuses = [
            '0' => false, // Новый платеж
            '2' => false, // Ожидает оплаты
            '3' => false, // Обрабатывается платежной системой
            '4' => false, // В процессе возврата платежной системой
            '5' => true, // Просрочен
            '6' => true, // Возвращен
            '7' => true, // Зачислен
            '8' => true, // Отменен
            '9' => true // Платеж возвращен платежной системой
        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking success status for deposit
     * @param int $status
     * @return bool
     */
    private static function _isSuccessDeposit(int $status): bool
    {
        return $status == 7;
    }

    /**
     * Checking final status for withdraw
     * @param int $status
     * @return boolean
     */
    private static function _isFinalWithdraw(int $status): bool
    {
        $statuses = [
            '1' => false, // Ожидает проверки модерацией
            '2' => false, // Проверен модерацией
            '3' => true, // Отозван модерацией
            '4' => false, // Заморожен
            '5' => false, // Разморожен
            '6' => false, // Обработка платежной системой
            '7' => false, // Зачисление
            '8' => true, // Проведен
            '9' => true, // Отменен
            '11' => true, // Возвращен
            '12' => false // Вывод создан в платежной системе, но еще не проведен

        ];

        return $statuses[$status] ?? false;
    }

    /**
     * Checking success status for withdraw
     * @param int $status
     * @return bool
     */
    private static function _isSuccessWithdraw(int $status): bool
    {
        return $status == 8;
    }

    /**
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return string
     */
    private static function _getCurrencyAlias(string $currency, string $method, string $way): string
    {
        $currencyID = self::_getCurrencyID($currency, $method, $way);

        if ($way === self::WAY_DEPOSIT) {
            $input = self::_getCurrencyData('input', $currencyID);
            return $input['als'] ?? '';
        }

        if ($way === self::WAY_WITHDRAW) {
            $output = self::_getCurrencyData('output', $currencyID);
            return $output['als'] ?? '';
        }

        return '';
    }

    /**
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return string
     */
    private static function _getCurrencyID(string $currency, string $method, string $way): string
    {
        $currencies = self::getSupportedCurrencies();

        if ($way === self::WAY_DEPOSIT) {
            return $currencies[$currency][$method]['d_id'] ?? '';
        }
        if ($way === self::WAY_WITHDRAW) {
            return $currencies[$currency][$method]['w_id'] ?? '';
        }

        return '';
    }

    /**
     * @param $way
     * @param null $currency_id
     * @return bool|null
     */
    private static function _getCurrencyData($way, $currency_id = null)
    {
        if (in_array($way, ['input', 'output'])) {
            $cache = Yii::$app->cache;

            $data = $cache->getOrSet([__CLASS__, '_getCurrencyData', $way], function () use ($way) {
                return self::_queryGet("paysystem-{$way}-payway");
            }, 60 * 60 * 24);

            if (empty($currency_id)) {
                return $data['data'];
            } else {
                return $data['data'][$currency_id] ?? false;
            }
        }

        return null;
    }

    /**
     * @param string $currency_id
     * @param string $way
     * @param string $type
     * @return bool|float|int
     */
    private static function _getMinMax(string $currency_id, string $way, string $type)
    {
        if (!in_array($type, ['min', 'max'])) {
            return false;
        }

        $data = [];

        if ($way === self::WAY_DEPOSIT) {
            $data = self::_getCurrencyData('input', $currency_id);
        }

        if ($way === self::WAY_WITHDRAW) {
            $data = self::_getCurrencyData('output', $currency_id);
        }

        return $data['amn'][$type] ?? false;

    }

    /**
     * @param string $currency
     * @param string $method
     * @return array|bool
     */
    private static function _getRequiredFields(string $currency, string $method)
    {
        $currencyID = self::_getCurrencyID($currency, $method, self::WAY_WITHDRAW);
        $data = self::_getCurrencyData('output', $currencyID);

        if (!isset($data['prm'])) {
            return false;
        }

        $fields = [];

        foreach ($data['prm'] as $item) {
            $fields[$item['al']] = [
                'label' => $item['tt'],
                'regex' => $item['re']
            ];
        }

        return $fields;
    }

    /**
     * @param int|float $amount
     * @param string $currency
     * @param string $method
     * @return bool|float|int
     */
    private static function _getCommission($amount, string $currency, string $method)
    {
        $currencyID = self::_getCurrencyID($currency, $method, self::WAY_WITHDRAW);
        $data = self::_getCurrencyData('output', $currencyID);

        if (!isset($data['fee']['out'])) {
            return false;
        }

        $commission = 0;

        if (isset($data['fee']['out']['rt'])) {
            $commission = round($amount * $data['fee']['out']['rt'] / 100, 2);
        }

        if (isset($data['fee']['out']['fix'])) {
            $commission += $data['fee']['out']['fix'];
        }

        if (isset($data['fee']['out']['min']) && $data['fee']['out']['min'] > $commission) {
            $commission = $data['fee']['out']['min'];
        }

        if (isset($data['fee']['out']['max']) && $data['fee']['out']['max'] < $commission) {
            $commission = $data['fee']['out']['max'];
        }

        return $commission;
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

        if (isset($methods[$currency][$method][$way])) {
            foreach (array_keys($methods[$currency][$method][$way]) as $field) {
                if (!in_array($field, array_keys($requisites))) {
                    return "Required param '{$field}' not found";
                }
            }
        }

        return true;
    }
}