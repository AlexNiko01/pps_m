<?php

namespace pps\bitgo;

use api\classes\ApiError;
use pps\payment\ICryptoCurrency;
use pps\querybuilder\QueryBuilder;
use Yii;
use yii\db\Exception;
use yii\web\Response;
use pps\payment\Payment;
use yii\base\InvalidParamException;

/**
 * ping url: https://www.bitgo.com/api/v1/ping
 * Class BitGo
 * @package pps\gourl
 */
class BitGo extends Payment implements ICryptoCurrency
{
    const TEST_API_URL = 'https://test.bitgo.com/api/v2/';
    const API_URL = 'https://bitgo.com/api/v2/';
    const MIN_CONFIRMS = 2;

    private $_wallet_url;
    /** @var string */
    private $_access_token;
    /** @var string */
    private $_wallet_id;
    /** @var string */
    private $_password;
    /** @var bool */
    private $_sandbox = false;


    /**
     * Filling the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (!$data['wallet_url']) {
            throw new InvalidParamException('wallet_url empty');
        }
        if (!$data['access_token']) {
            throw new InvalidParamException('access_token empty');
        }
        if (!$data['wallet_id']) {
            throw new InvalidParamException('wallet_id empty');
        }
        if (!$data['password']) {
            throw new InvalidParamException('password empty');
        }

        $this->_wallet_url = $data['wallet_url'];
        $this->_access_token = $data['access_token'];
        $this->_wallet_id = $data['wallet_id'];
        $this->_password = $data['password'];
        $this->_sandbox = $data['sandbox'] ?? false;
    }

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        return self::getErrorAnswer(self::ERROR_METHOD);
    }

    /**
     * Invoicing for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        return self::getErrorAnswer(self::ERROR_METHOD);
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

        $need = ['type', 'walletId', 'hash', 'state', 'pendingApprovalId'];

        if (!self::checkRequiredParams($need, $receiveData)) {
            Yii::error([
                'receive_data' => $receiveData,
                'need' => $need
            ], 'payment-bitgo-receive');
            return false;
        }

        switch ($receiveData['state']) {
            case 'pending':
                $transaction->status = self::STATUS_PENDING;
                break;
            case 'approved':
                $transaction->status = self::STATUS_SUCCESS;
                break;
            case 'rejected':
                $transaction->status = self::STATUS_CANCEL;
                break;
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
        $validate = self::_validateTransaction($params['currency'], $params['payment_method'], $params['amount'], self::WAY_WITHDRAW);

        if ($validate !== true) {
            return self::getErrorAnswer($validate);
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
                'fields' => self::_getFields($params['currency'], $params['payment_method'], self::WAY_WITHDRAW),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'merchant_write_off' => null,
                'buyer_receive' => null,
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
        $transaction = $params['transaction'];
        $requests = $params['requests'];
        $answer = [];

        $coefficient = self::getCoefficient($transaction->currency);
        $transaction->amount = $transaction->amount / $coefficient;
        $transaction->receive = $transaction->amount;

        $normalizeCoeff = self::_getNormalizeCoeff($transaction->currency);

        $amount = $transaction->amount * $normalizeCoeff;

        try {
            $transaction->save(false);
        } catch (Exception $e) {
            self::getErrorAnswer(self::ERROR_TRANSACTION_ID);
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $validate = $this->_validateTransaction($transaction->currency, $transaction->payment_method, $transaction->amount, self::WAY_WITHDRAW);

        if ($validate !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            $this->logger->log($transaction->id, 100, $validate);

            return self::getErrorAnswer($validate);
        }

        if (!empty($params['commission_payer'])) {
            return [
                'status' => 'error',
                'message' => self::ERROR_COMMISSION_PAYER,
                'code' => ApiError::COMMISSION_ERROR
            ];
        }

        $requisites = json_decode($transaction->requisites, true);

        $message = static::_checkRequisites($requisites, $transaction->currency, $transaction->payment_method, self::WAY_WITHDRAW);

        if ($message !== true) {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);
            $this->logger->log($transaction->id, 100, $message);

            return self::getErrorAnswer($message, ApiError::REQUISITE_FIELD_NOT_FOUND);
        }

        $session = $this->_getSession();

        if (!isset($session['session']['unlock'])) {
            $unlock = $this->_unlock();
            if (!$unlock) {
                $this->logger->log($transaction->id, 100, "Can't unlock!");
            } else {
                $this->logger->log($transaction->id, 100, "Unlocked!");
            }
        }

        $query = [
            'amount' => (string)$amount,
            'address' => $requisites['address'],
            'walletPassphrase' => $this->_password,
            'comment' => $transaction->comment,
            'minConfirms' => self::MIN_CONFIRMS,
        ];

        $coin = strtolower($transaction->currency);

        $url = "{$this->_wallet_url}/api/v2/{$coin}/wallet/{$this->_wallet_id}/sendcoins";

        $transaction->query_data = json_encode($query);
        $transaction->save(false);

        $this->logger->log($transaction->id, 2, $transaction->query_data);

        $request = (new QueryBuilder($url))
            ->setParams($query)
            ->setHeader('Authorization', "Bearer {$this->_access_token}")
            ->json()
            ->asPost()
            ->send();

        $withdrawResponse = $request->getResponse(true);
        $curlErrno = $request->getErrno();

        Yii::info(['query' => $query, 'result' => $withdrawResponse], 'payment-bitgo-withdraw');

        $transaction->result_data = json_encode($withdrawResponse);
        $transaction->save(false);

        $this->logger->log($transaction->id, 3, $transaction->result_data);

        if (isset($withdrawResponse['status']) && $withdrawResponse['status'] == 'signed') {
            $transaction->external_id = $withdrawResponse['txid'];

            $transaction->save(false);

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);

            if (!in_array($transaction->status, self::getFinalStatuses())) {
                $params['updateStatusJob']->transaction_id = $transaction->id;
            }
        } elseif ($curlErrno == CURLE_OPERATION_TIMEOUTED) {
            if (!in_array($transaction->status, self::getFinalStatuses())) {
                $transaction->status = self::STATUS_TIMEOUT;
                $transaction->save(false);

                $params['updateStatusJob']->transaction_id = $transaction->id;
            }

            $this->logger->log($transaction->id, 100, self::STATUS_NETWORK_ERROR);

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } elseif ($curlErrno > CURLE_OK) {

            $transaction->status = self::STATUS_NETWORK_ERROR;
            $transaction->save(false);

            $this->logger->log($transaction->id, 100, self::STATUS_NETWORK_ERROR);

            $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        } else {
            $transaction->status = self::STATUS_ERROR;
            $transaction->save(false);

            $answer = self::getErrorAnswer($withdrawResponse['error'] ?? self::ERROR_OCCURRED);

            $this->logger->log($transaction->id, 100, [$answer, $request->getInfo()]);
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
            /*$request = (new QueryBuilder(self::getApiUrl() . strtolower($transaction->currency) . "/wallet/{$this->_wallet_id}/tx/{$transaction->external_id}"))
                ->setHeader('Authorization', "Bearer {$this->_access_token}")
                ->send();

            $response = $request->getResponse(true);
            Yii::info([$response, $request->getInfo()], 'updateStatusBitGo');*/

            $response = self::_getTx($transaction->currency, $this->_wallet_id, $transaction->external_id, $this->_access_token);

            $conf = $response['confirmations'] ?? 0;

            if (empty($transaction->write_off) && isset($response['fee'])) {
                $coef = self::_getNormalizeCoeff($transaction->currency);
                $transaction->write_off = $transaction->amount + $response['fee'] / $coef;
            }

            if ($conf > 0 && $conf < self::MIN_CONFIRMS) {
                $transaction->status = self::STATUS_UNCONFIRMED;
            } elseif ($conf >= self::MIN_CONFIRMS) {
                $transaction->status = self::STATUS_SUCCESS;
            }

            $transaction->save(false);

            $this->logger->log($transaction->id, 8, 'A lot of data, for viewing run command: curl -H "Authorization: Bearer ' . $this->_access_token .'" https://test.bitgo.com/api/v2/' . strtolower($transaction->currency) .'/wallet/5b2a2ed86c3025e90309f01caad64d79/tx/' . $transaction->external_id);

            return true;
        }

        return false;
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
        $request = (new QueryBuilder(self::getApiUrl() . strtolower($currency) . "/wallet/{$this->_wallet_id}/address"))
            ->setParams([
                'label' => "{$brand_id}:{$buyer_id}"
            ])
            ->setHeader('Authorization', "Bearer {$this->_access_token}")
            ->asPost()
            ->send();

        $result = $request->getResponse(true);

        Yii::info(['result' => $result], 'payment-bitgo-getAddress');

        if (isset($result['address'])) {
            return [
                'data' => [
                    'address' => $result['address']
                ],
            ];
        } else {
            return self::getErrorAnswer($result['error'] ?? self::ERROR_OCCURRED);
        }
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        return $this->_sandbox ? self::TEST_API_URL : self::API_URL;
    }

    /**
     * TODO fix address in prod
     * @param $currency
     * @return bool|string
     */
    private static function getWalletIdByAddress($currency, $address, $token)
    {
        $walletIdRequest = (new QueryBuilder('https://test.bitgo.com/api/v2/' . strtolower($currency) . "/wallet/address/" . urlencode($address)))
            ->setHeader('Authorization', "Bearer {$token}")
            ->send();

        $walletIdResult = $walletIdRequest->getResponse(true);
        return $walletIdResult['id'] ?? false;
    }

    /**
     * @return mixed
     */
    private function _getSession()
    {
        $request = (new QueryBuilder(self::getApiUrl() . "user/session"))
            ->setHeader('Authorization', "Bearer {$this->_access_token}")
            ->send();

        return $request->getResponse(true);
    }

    /**
     * @return mixed
     */
    private function _unlock()
    {
        $request = (new QueryBuilder(self::getApiUrl() . "user/unlock"))
            ->setParams([
                'otp' => '0000000'
            ])
            ->setHeader('Authorization', "Bearer {$this->_access_token}")
            ->json()
            ->asPost()
            ->send();

        return $request->getResponse(true);
    }

    /**
     * @return mixed
     */
    private function _lock()
    {
        $request = (new QueryBuilder(self::getApiUrl() . "user/lock"))
            ->setHeader('Authorization', "Bearer {$this->_access_token}")
            ->asPost()
            ->send();

        return $request->getResponse(true);
    }

    /**
     * @param $message
     * @param int $code
     * @return array
     */
    public static function getErrorAnswer($message, $code = ApiError::PAYMENT_SYSTEM_ERROR)
    {
        return [
            'status' => 'error',
            'message' => $message,
            'code' => $code
        ];
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
     * TODO implement it
     * Getting query for search transaction after success or fail payment
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
        return $data['hash'] ?? 0;
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
     * Fill incoming transaction
     * @param int $paymentSystemId
     * @param object $userAddress
     * @param array $receiveData
     * @return array|bool
     */
    public static function fillTransaction($paymentSystemId, $userAddress, $receiveData)
    {
        $currency = $receiveData['coin'] ?? null;
        $txid = $receiveData['hash'] ?? null;
        $wallet = $receiveData['wallet'] ?? '';
        $token = $receiveData['access_token'] ?? '';
        $tx = self::_getTx($currency, $wallet, $txid, $token);

        if (!$tx) {
            Yii::error([
                'message' => "Can't find transaction",
                'data' => $receiveData,
            ], 'payment-bitgo-fillTransaction');
            return false;
        }

        $status = self::STATUS_CREATED;
        $confirmations = $tx['confirmations'] ?? 0;

        if (isset($tx['double_spend']) && $tx['double_spend']) {
            $status = self::STATUS_DSPEND;
        } elseif ($confirmations < self::MIN_CONFIRMS) {
            $status = self::STATUS_UNCONFIRMED;
        } elseif ($confirmations >= self::MIN_CONFIRMS) {
            $status = self::STATUS_SUCCESS;
        }

        $address = null;
        $amount = 0;
        $write_off = 0;

        $user = null;

        $coeff = self::_getNormalizeCoeff($currency);

        switch ($currency) {
            case 'ltc':
            case 'tltc':
            case 'btc':
            case 'tbtc':
                $entries = $tx['outputs'] ?? [];
                break;
            case 'rmg':
            case 'trmg':
            case 'xrp':
            case 'txrp':
            case 'eth':
            case 'teth':
            case 'bat':
            case 'brd':
            case 'cvc':
            case 'fun':
            case 'gnt':
            case 'knc':
            case 'mkr':
            case 'nmr':
            case 'omg':
            case 'pay':
            case 'qrl':
            case 'rep':
            case 'rdn':
            case 'wax':
            case 'zil':
            case 'zrx':
            case 'terc':
                $entries = $tx['entries'] ?? [];
                break;
            default:
                $entries = [];
                $coeff = 1;
        }

        foreach ($entries as $entry) {
            if (isset($entry['wallet']) && $entry['wallet'] == $wallet) {
                $address = $entry['address'];
                $amount = $entry['value'] / $coeff;
                $write_off = ($entry['value'] + $tx['fee']) / $coeff;

                $user = $userAddress->where([
                    'address' => $address,
                    'payment_system_id' => $paymentSystemId,
                    'currency' => strtoupper($currency)
                ])
                    ->asArray()
                    ->one();

                break;
            }
        }


        if (!$address || !$amount) {
            Yii::error([
                'message' => "Address or amount!",
                'address' => $address,
                'amount' => $amount,
            ], 'payment-bitgo-fillTransaction');

            return false;
        }

        if (empty($user)) {
            Yii::error([
                'message' => "User not found!",
                'address' => $address,
                'payment_system_id' => $paymentSystemId,
                'currency' => strtoupper($currency),
            ], 'payment-bitgo-fillTransaction');

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
            'currency' => $user['currency'],
            'amount' => $amount,
            'write_off' => $write_off ?? $amount,
            'refund' => $amount,
            'payment_method' => 'bitgo',
            'comment' => "Deposit from user #{$user['user_id']}",
            'merchant_transaction_id' => $receiveData['hash'],
            'external_id' => $receiveData['hash'],
            'commission_payer' => self::COMMISSION_BUYER,
            'buyer_id' => $user['user_id'],
            'status' => $status,
            'urls' => json_encode($urls)
        ];
    }

    /**
     * @param $currency
     * @return int
     */
    private static function _getNormalizeCoeff($currency)
    {
        $currency = strtolower($currency);

        switch ($currency) {
            case 'ltc':
            case 'tltc':
            case 'btc':
            case 'tbtc':
                $coeff = 10**8;
                break;
            case 'rmg':
            case 'trmg':
            case 'xrp':
            case 'txrp':
                $coeff = 10**6;
                break;
            case 'eth':
            case 'teth':
            case 'bat':
            case 'brd':
            case 'cvc':
            case 'fun':
            case 'gnt':
            case 'knc':
            case 'mkr':
            case 'nmr':
            case 'omg':
            case 'pay':
            case 'qrl':
            case 'rep':
            case 'rdn':
            case 'wax':
            case 'zil':
            case 'zrx':
            case 'terc':
                $coeff = 10**18;
                break;
            default:
                $coeff = 1;
        }

        return $coeff;
    }

    /**
     * Get success answer for stopping send callback data
     * @return array|string
     */
    public static function getSuccessAnswer()
    {
        return '';
    }

    /**
     * TODO fix address in prod
     * @param $currency
     * @param $wallet
     * @param $txid
     * @param $token
     * @return mixed|null
     */
    private static function _getTx($currency, $wallet, $txid, $token)
    {
        $currency = strtolower($currency);

        $request = (new QueryBuilder("https://bitgo.com/api/v2/{$currency}/wallet/{$wallet}/tx/{$txid}"))
            ->setHeader('Authorization', "Bearer {$token}")
            ->send();

        $result = $request->getResponse(true);

        if (isset($result['error'])) {
            return null;
        } else {
            return $result;
        }
    }

    /**
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
            return "Payment system does not supports '{$way}'";
        }

        /*if (isset($method['min']) && isset($method['max'])) {
            if ($method['min'] > $amount || $method['max'] <= $amount) {
                return "Amount should to be more than '{$method['min']}' and less than '{$method['max']}'";
            }
        }

        if (isset($method['min'])) {
            if ($method['min'] > $amount) {
                return "Amount should to be more than '{$method['min']}'";
            }
        }*/

        return true;
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
     * @param array $requisites
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return bool|string
     */
    private static function _checkRequisites(array $requisites, string $currency, string $method, string $way)
    {
        $currencies = static::getSupportedCurrencies();

        if (isset($currencies[$currency][$method]['fields'][$way])) {
            foreach (array_keys($currencies[$currency][$method]['fields'][$way]) as $field) {
                if (!in_array($field, array_keys($requisites))) {
                    return "Required param '{$field}' not found";
                }
            }
        }

        return true;
    }
}