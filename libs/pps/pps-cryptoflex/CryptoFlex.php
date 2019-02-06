<?php

namespace pps\cryptoflex;

use api\classes\ApiError;
use api\classes\ApiProxy;
use api\models\UserAddress;
use backend\models\Node;
use backend\models\PaymentSystem;
use backend\models\PaymentSystemExternalData;
use common\models\Transaction;
use console\jobs\UpdateStatusJob;
use GuzzleHttp\Exception\GuzzleException;
use pps\cryptoflex\CryptoFlexApi\Callbacks\OrderCallback;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoOrderResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoPayoutResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoPayoutStatusResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\CryptoFlexResponseInterface;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexApi;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexItemNotExistException;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexEndpoints;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\Currencies\CryptoFlexCurrency;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;
use pps\payment\ICryptoCurrency;
use pps\payment\IndividualSettingsInterface;
use pps\payment\SupportDenominationInterface;
use Yii;
use pps\payment\Payment;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\base\UserException;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class CryptoFlex
 * @package pps\cryptoflex
 */
class CryptoFlex extends Payment implements ICryptoCurrency, IndividualSettingsInterface, SupportDenominationInterface
{
    /**@var CryptoFlexConfig $config */
    private $config;

    /**@var CryptoFlexApi $cryptoFlexApi */
    private $cryptoFlexApi;


    /**
     * Fill the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        if (empty($data['partner_id'])) {
            throw new InvalidParamException('Partner Id empty');
        }
        if (empty($data['secret_key'])) {
            throw new InvalidParamException('Secret Key empty');
        }
        /*
                if (empty($data['wallet'])) {
                    throw new InvalidParamException('Wallet Address empty');
                }
        */
        try {
            $this->config = new CryptoFlexConfig(
                CryptoFlexPaymentMethod::CRYPTO,
                $data['sandbox'] ? CryptoFlexEndpoints::TEST : CryptoFlexEndpoints::LIVE
            );
            $this->config->setPartnerId(ArrayHelper::getValue($data, 'partner_id', ''));
            $this->config->setSecretKey(ArrayHelper::getValue($data, 'secret_key', ''));
            $this->config->setWalletAddress(ArrayHelper::getValue($data, 'wallet', ''));
            $this->config->setFeeLevel(ArrayHelper::getValue($data, 'fee_level', ''));
            $this->config->setConfirmationsPayout(ArrayHelper::getValue($data, 'confirmations_payout', ''));
            $this->config->setConfirmationsTrans(ArrayHelper::getValue($data, 'confirmations_trans', ''));
            $this->config->setConfirmationsWithdraw(ArrayHelper::getValue($data, 'confirmations_withdraw', ''));
            $this->config->setDenomination(ArrayHelper::getValue($data, 'denomination', ''));
        } catch (CryptoFlexAPIException $e) {
            $this->logAndDie(
                'Required parameters of config is incorrect',
                $e->getMessage(),
                'CryptoFlex fill credentials error',
                'cryptoflex-receive'
            );
        }
        $this->cryptoFlexApi = new CryptoFlexApi($this->config);
    }

    /**
     * Preliminary calculation of the invoice.
     * Get required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {
        return self::prepareErrorReturn(self::ERROR_METHOD);
    }

    /**
     * Invoice for payment
     * @param array $params
     * @return array
     */
    public function invoice(array $params): array
    {
        return self::prepareErrorReturn(self::ERROR_METHOD);
    }

    /**
     * Method for receiving and updating statuses
     * @param array $data
     * @return bool
     */
    public function receive(array $data): bool
    {
        /** @var Transaction $transaction */
        $transaction = $data['transaction'];

        if (\in_array((int)$transaction->status, self::getFinalStatuses(), true)) {
            return true;
        }
        /** @var OrderCallback $receive */
        $receive = $this->cryptoFlexApi->getCallback($data['receive_data']);
        if (!$receive) {
            $this->logAndDie(
                "CryptoFlex receive() error ({$transaction->id})",
                $this->cryptoFlexApi->lastErrorMessage,
                'CryptoFlex callback',
                'cryptoflex-receive'
            );
        }

        if ((float)$transaction->amount !== (float)$receive->amount) {
            $this->logAndDie(
                "CryptoFlex receive() transaction amount not equal received amount ({$transaction->id})",
                "Transaction amount = {$transaction->amount}\nreceived amount = {$receive->amount}",
                'Transaction amount not equal received amount'
            );
        }
        $transaction->result_data = (string) $receive;
        if (!$transaction->external_id) {
            $transaction->external_id = $receive->getExternalTransactionId();
        }

        if ($this->cryptoFlexApi->isSuccessDeposit($receive->status)) {
            $transaction->write_off = $receive->amount + $receive->fee_value;
            $transaction->refund = $receive->amount;
            $transaction->commission = $receive->fee_value;
        }
        $transaction->status = $this->cryptoFlexApi->convertDepositStatus($receive->status);
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
        if (!empty($params['commission_payer'])) {
            return self::prepareErrorReturn(self::ERROR_COMMISSION_PAYER);
        }

        try {
            $this->validateTransaction(
                $params['currency'],
                $params['payment_method'],
                $params['amount'] / $this->config->getDenomination(),
                self::WAY_WITHDRAW
            );
        } catch (CryptoFlexAPIException $e) {
            return self::prepareErrorReturn($e->getMessage(), ApiError::LOW_BALANCE);
        } catch (GuzzleException $e) {
            return self::prepareErrorReturn($e->getMessage(), ApiError::PAYMENT_SYSTEM_ERROR);
        }

        return [
            'data' => [
                'fields' => static::getFields($params['currency'], $params['payment_method'], self::WAY_WITHDRAW),
                'currency' => $params['currency'],
                'amount' => $params['amount'],
                'buyer_write_off' => null,
                'merchant_refund' => null,
            ]
        ];
    }

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     * @throws CryptoFlexValidationException
     * @throws GuzzleException
     */
    public function withDraw(array $params): array
    {
        /** @var Transaction $transaction */
        $transaction = $params['transaction'];

        $requests = $params['requests'];

        try {
            $this->validateTransaction(
                $transaction->currency,
                $transaction->payment_method,
                $transaction->amount,
                self::WAY_WITHDRAW
            );

            $requisites = json_decode($transaction->requisites, true);

            self::checkRequisites(
                $requisites,
                $transaction->currency,
                $transaction->payment_method,
                self::WAY_WITHDRAW
            );
        } catch (CryptoFlexItemNotExistException $e) {
            return $this->prepareFinalTransactionError($transaction, $e->getMessage(), ApiError::REQUIRED_FIELD_ERROR);
        } catch (CryptoFlexAPIException $e) {
            return $this->prepareFinalTransactionError($transaction, $e->getMessage(), ApiError::PAYMENT_SYSTEM_ERROR);
        }

        $transaction->status = self::STATUS_CREATED;
        $transaction->save(false);

        if (!empty($params['commission_payer'])) {
            return $this->prepareFinalTransactionError(
                $transaction,
                self::ERROR_COMMISSION_PAYER,
                ApiError::COMMISSION_ERROR
            );
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);

        $queryData = [
            'partner_withdraw_id' => $transaction->id,
            'amount' => $transaction->amount,
            'crypto_currency' => $transaction->currency,
            'wallet_address' => $this->config->getWalletAddress(),
            'confirmations_withdraw' => $this->config->getConfirmationsWithdraw(),
            'fee_level' => $this->config->getFeeLevel(),
            'requisites' => $requisites
        ];

        /**@var CryptoPayoutResponse $response */
        $response = $this->cryptoFlexApi->createWithdrawal($queryData, $transaction->payment_method);

        $transaction->query_data = json_encode($this->cryptoFlexApi->getRawRequest()); //в виде массива
        $transaction->result_data = $this->cryptoFlexApi->getRawResponse() === []
            ? json_encode(['error' => $this->cryptoFlexApi->lastErrorMessage])
            : json_encode($this->cryptoFlexApi->getRawResponse());

        $this->logger->log($transaction->id, 2, $transaction->query_data);
        $this->logger->log($transaction->id, 3, $transaction->result_data);
        $transaction->save(false);

        $params['updateStatusJob']->transaction_id = $transaction->id;

        return $this->handleResponseToPPS(
            $response,
            $transaction,
            function (Transaction $transaction, CryptoPayoutResponse $response) use ($params) {
                $transaction->status = $this->cryptoFlexApi->convertWithdrawalStatus($response->status);
                $transaction->external_id = $response->withdraw_id;
                $transaction->receive = $response->amount;
                $transaction->write_off = $response->amount + $response->fee_value;
                $transaction->commission = $response->fee_value;
                $transaction->save(false);
                $params['updateStatusJob']->transaction_id = $transaction->id;
                $answer['data'] = $transaction::getWithdrawAnswer($transaction, $this->getDenomination());
                $answer['type'] = Transaction::TYPE_CRYPTO;
                return $answer;
            },
            true
        );
    }

    /**
     * Update not final statuses
     * @param Transaction $transaction
     * @param null|object $model_req
     * @return bool
     * @throws CryptoFlexValidationException
     * @throws GuzzleException
     */
    public function updateStatus($transaction, $model_req = null): bool
    {
        if (!\in_array((int)$transaction->status, self::getNotFinalStatuses(), true)) {
            return false;
        }

        if ($transaction->way !== self::WAY_WITHDRAW) {
            return false;
        }

        /** @var CryptoPayoutStatusResponse $status */
        $status = $this->cryptoFlexApi->getWithdrawalStatus(
            $transaction->currency,
            $transaction->id,
            $this->config->getWalletAddress()
        );

        switch ($this->cryptoFlexApi->errorCode) {
            case CryptoFlexApi::STATUS_SUCCESS:
                if (empty($transaction->external_id)) {
                    $transaction->external_id = $status->withdraw_id;
                    $transaction->save(false);
                }
                if ($model_req !== null) {
                    $this->logger->log($transaction->id, 8, $status->getResponseBody());
                }
                Yii::info($status->getResponseBody(), 'payment-cryptoflex-status');
                $transaction->status = $this->cryptoFlexApi->convertWithdrawalStatus($status->status);
                $transaction->write_off = $status->amount + $status->fee_value;
                $transaction->commission = $status->fee_value;
                //$transaction->type = Transaction::TYPE_CRYPTO;
                $transaction->save(false);
                return true;
            case CryptoFlexApi::STATUS_ERROR_PROVIDER:
            case CryptoFlexApi::STATUS_ERROR_CONNECT:
            case CryptoFlexApi::STATUS_ERROR_CLIENT:
            case CryptoFlexApi::STATUS_ERROR_SERVER:
            case CryptoFlexApi::STATUS_ERROR_REQUEST:
            case CryptoFlexApi::STATUS_ERROR_UNDEFINED:
                Yii::error($this->cryptoFlexApi->lastErrorMessage, 'payment-cryptoflex-status');
                Yii::error($this->cryptoFlexApi->getRawRequest(), 'payment-cryptoflex-status');
                Yii::error($this->cryptoFlexApi->getRawResponse(), 'payment-cryptoflex-status');
        }
        return false;
    }

    /**
     * Get model for validation incoming data
     * @return Model
     */
    public static function getModel(): Model
    {
        return new \pps\cryptoflex\Model();
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
    public static function getTransactionID(array $data): int
    {
        return null;
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
    public static function getResponseFormat($way = self::WAY_DEPOSIT): string
    {
        return Response::FORMAT_HTML;
    }

    /**
     * Validation transaction before send to payment system
     * @param string $currency
     * @param string $paymentMethod
     * @param float $amount
     * @param string $way
     * @return bool|string
     * @throws CryptoFlexItemNotExistException
     * @throws CryptoFlexValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function validateTransaction(string $currency, string $paymentMethod, float $amount, string $way)
    {
        $currencies = CryptoFlexCurrency::getCurrencyObject($paymentMethod);
        $currencies->isSupportedWay($way);
        $currencies->isSupportedCurrency($currency);
        $balance = $this->cryptoFlexApi->getWalletBalance($currency, $this->config->getWalletAddress());
        if ($amount >= $balance->balance) {
            throw new CryptoFlexItemNotExistException('Insufficient balance');
        }
        return true;
    }

    /**
     * Get fields for filling
     * @param string $currency
     * @param string $paymentMethod
     * @param string $way
     * @return array
     */
    private static function getFields(string $currency, string $paymentMethod, string $way): array
    {
        try {
            return CryptoFlexCurrency::getCurrencyObject($paymentMethod)->getFields($currency, $way);
        } catch (CryptoFlexItemNotExistException $e) {
            return [];
        }
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        try {
            return CryptoFlexCurrency::getAllApiFields();
        } catch (CryptoFlexItemNotExistException $e) {
            return [];
        }
    }

    /**
     * Get supported currencies
     * @return array
     * @throws CryptoFlexItemNotExistException
     */
    public static function getSupportedCurrencies(): array
    {
        return CryptoFlexCurrency::getAllSupportedCurrencies();
    }

    /**
     * @param array $requisites
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return bool|string
     * @throws CryptoFlexItemNotExistException
     */
    private static function checkRequisites(array $requisites, string $currency, string $method, string $way)
    {
        $fields = CryptoFlexCurrency::getCurrencyObject($method)->getFields($currency, $way);
        foreach ($fields as $fieldName => $field) {
            if (!array_key_exists($fieldName, $requisites)
                && ArrayHelper::getValue($fields, [$fieldName, 'required'], false)) {
                throw new CryptoFlexItemNotExistException("Required param '{$fieldName}' not found");
            }
        }
        return true;
    }


    /**
     * @param $message
     * @param int $code
     * @return array
     */
    private static function prepareErrorReturn($message, $code = ApiError::PAYMENT_SYSTEM_ERROR): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'code' => $code
        ];
    }

    /**
     * @param Transaction $transaction
     * @param $message
     * @param $errorCode
     * @return array
     */
    private function prepareFinalTransactionError(Transaction $transaction, $message, $errorCode): array
    {
        $transaction->status = self::STATUS_ERROR;
        $transaction->result_data = json_encode(['error' => $message]);
        $transaction->save(false);
        return self::prepareErrorReturn($message, $errorCode);
    }

    /**
     * @param CryptoFlexResponseInterface $response
     * @param Transaction $transaction
     * @param callable $successFoo
     * @param bool $updateJobStart
     * @return array
     */
    private function handleResponseToPPS(
        CryptoFlexResponseInterface $response,
        Transaction $transaction,
        callable $successFoo,
        bool $updateJobStart = false
    ): array {
        switch ($this->cryptoFlexApi->errorCode) {
            case CryptoFlexApi::STATUS_SUCCESS:
                return $successFoo($transaction, $response);
            case CryptoFlexApi::STATUS_ERROR_CONNECT:
                $transaction->status = self::STATUS_TIMEOUT;
                break;
            case CryptoFlexApi::STATUS_ERROR_PROVIDER:
            case CryptoFlexApi::STATUS_ERROR_CLIENT:
                return $this->prepareFinalTransactionError(
                    $transaction,
                    $this->cryptoFlexApi->lastErrorMessage,
                    ApiError::PAYMENT_SYSTEM_ERROR
                );
            case CryptoFlexApi::STATUS_ERROR_SERVER:
            case CryptoFlexApi::STATUS_ERROR_REQUEST:
                $transaction->status = self::STATUS_NETWORK_ERROR;
                $transaction->result_data = json_encode(
                    ['error_message' => $this->cryptoFlexApi->exception->getMessage()]
                );
                break;
            case CryptoFlexApi::STATUS_ERROR_UNDEFINED:
                $answer['message'] = self::ERROR_OCCURRED;
                $answer['status'] = 'error';
                $message = "Request url = '" . $this->cryptoFlexApi->getRequestUrl();
                $message .= "\nRequest result = " . $this->cryptoFlexApi->getRawResponse() ?? '';
                Yii::error($message, 'payment-cryptoflex-transaction');
                return $answer;
                break;
            default:
                $transaction->status = Payment::STATUS_ERROR;
                break;
        }
        $transaction->save(false);
        $answer['data'] = $transaction->way === Payment::WAY_DEPOSIT
            ? $transaction::getDepositAnswer($transaction, $this->config->getDenomination())
            : $transaction::getWithdrawAnswer($transaction, $this->config->getDenomination());

        if ($updateJobStart) {
            $statusJob = new UpdateStatusJob();
            $statusJob->transaction_id = $transaction->id;
        }
        return $answer;
    }

    /**
     * Get new address for user
     * @param $buyer_id
     * @param $callback_url
     * @param $brand_id
     * @param $currency
     * @return array
     * @throws CryptoFlexValidationException
     * @throws GuzzleException
     */
    public function getAddress($buyer_id, $callback_url, $brand_id, $currency): array
    {
        $data = [
            'partner_id' => $this->config->getPartnerId(),
            'partner_invoice_id' => $buyer_id . '_' . $brand_id . '_' . time(),
            'payout_address' => $this->config->getWalletAddress(),
            'callback' => $callback_url,
            'crypto_currency' => $currency,
            'confirmations_trans' => $this->config->getConfirmationsTrans(),
            'confirmations_payout' => $this->config->getConfirmationsPayout(),
            'fee_level' => $this->config->getFeeLevel(),
        ];

        /**
         * @var CryptoOrderResponse $depositAddressResponse
         */
        $depositAddressResponse = $this->cryptoFlexApi->getAddress($data, $this->config->getPaymentMethod());
        if ($this->cryptoFlexApi->errorCode !== CryptoFlexApi::STATUS_SUCCESS) {
            return self::prepareErrorReturn($this->cryptoFlexApi->lastErrorMessage);
        }
        return [
            'data' => [
                'address' => $depositAddressResponse->address
            ],
            'type' =>  Transaction::TYPE_CRYPTO
        ];
    }

    /**
     * Fill incoming transaction
     * @param $paymentSystemId
     * @param ActiveQuery $userAddressQuery
     * @param $receiveData
     * @return array|bool
     */
    public static function fillTransaction($paymentSystemId, $userAddressQuery, $receiveData)
    {
        /**
         * @var UserAddress $userAddress
         */
        $userAddress = $userAddressQuery->where(
            [
                'address' => $receiveData['address'] ?? '',
                'payment_system_id' => $paymentSystemId,
                'currency' => strtoupper($receiveData['crypto_currency'] ?? '')
            ]
        )->one();
        if ($userAddress === null || $userAddress->userPaymentSystem === null) {
            Yii::error('UserPaymentSystem not found in' . __METHOD__, 'payment-cryptoflex');
            return false;
        }

        try {
            /**
             * @var self $ps
             */
            $ps = ApiProxy::loadPpsByUserPaymentSystem($userAddress->userPaymentSystem);
        } catch (UserException $e) {
            Yii::error($e, 'payment-cryptoflex');
            return false;
        }
        $callback = $ps->cryptoFlexApi->getCallback($receiveData);
        if ($ps->cryptoFlexApi->getErrorCode() !== CryptoFlexApi::STATUS_SUCCESS) {
            Yii::error($ps->cryptoFlexApi->lastErrorMessage, 'payment-cryptoflex-callback');
            return false;
        }

        return [
            'brand_id' => $userAddress->brand_id,
            'payment_system_id' => $userAddress->payment_system_id,
            'way' => self::WAY_DEPOSIT,
            'currency' => $callback->crypto_currency,
            'amount' => $callback->amount,
            'write_off' => $callback->amount + $callback->fee_value,
            'refund' => $callback->amount,
            'payment_method' => 'cryptoflex',
            'comment' => "Deposit from user #{$userAddress->user_id}",
            'merchant_transaction_id' => $callback->getExternalTransactionId(),
            'external_id' => $callback->getExternalTransactionId(),
            'commission_payer' => self::COMMISSION_BUYER,
            'buyer_id' => $userAddress->user_id,
            'status' => $ps->cryptoFlexApi->convertDepositStatus($callback->status),
            'result_data' => (string) $callback,
            //'type' => Transaction::TYPE_CRYPTO
        ];
    }

    /**
     * method should return HTML with special settings markup (buttons, fields, etc...) in case PS
     * provide some setting which we can implement in our system
     * this elemets will be processing by UserPaymentSystemController::actionIndividualSettings($id) (via AJAX)
     *
     * @param array $params - additional parameters
     * @return string [path alias](guide:concept-aliases) (e.g. "@vendor/pps/pps-current/view/settings");
     */
    public static function getSettingsView(array $params = []): string
    {
        return '@vendor/pps/pps-cryptoflex/view/settings.php';
    }

    /**
     * handler for UserPaymentSystemController::actionIndividualSettings($id).
     * Implement of specific logic for import settings for current PS
     * @param string $actionName
     * @param array $params
     * @return mixed
     * @throws GuzzleException
     */
    public function runIndSettingsAction(string $actionName, array $params = [])
    {
        switch ($actionName) {
            case 'generateWallet':
                try {
                    if (!isset($params['currency']) || $params['currency'] === '') {
                        throw new Exception('Param currency not set');
                    }
                    $psId = ApiProxy::getPaymentSystemID();
                    $walletSetting = PaymentSystemExternalData::find()->where(
                        [
                            'payment_system_id' => PaymentSystem::getIdByCode('cryptoflex'),
                            'brand_id' => Node::getCurrentNode(),
                            'attr_code' => 'wallet',
                            'currency' => strtoupper($params['currency'])
                        ]
                    )->one();

                    if ($walletSetting && $walletSetting->value !== '') {
                        throw new Exception('Wallet address already set');
                    }
                    $walletAddress = $this->cryptoFlexApi->createWallet($params['currency']);
                    if ($this->cryptoFlexApi->errorCode !== CryptoFlexApi::STATUS_SUCCESS) {
                        throw new Exception($this->cryptoFlexApi->lastErrorMessage);
                    }
                    return [
                        'error' => 'ok',
                        'message' => 'Wallet was generated',
                        'data' => ['wallet' => $walletAddress->wallet_address]
                    ];
                } catch (\Exception $e) {
                    return ['error' => 'error', 'message' => 'Wallet create error: ' . $e->getMessage()];
                }
                break;
            case 'getBalance':
                try {
                    if (!isset($params['currency'])) {
                        throw new Exception('Param currency not set');
                    }
                    if (!isset($params['wallet']) || $params['wallet'] === '') {
                        throw new Exception('Param wallet not set');
                    }

                    $balance = $this->cryptoFlexApi->getWalletBalance($params['currency'], $params['wallet']);
                    if ($this->cryptoFlexApi->errorCode !== CryptoFlexApi::STATUS_SUCCESS) {
                        throw new Exception($this->cryptoFlexApi->lastErrorMessage);
                    }

                    return [
                        'error' => 'ok',
                        'data' => ['balance' => (string) $balance->balance],
                        'message' => 'Balance was loaded',
                    ];
                } catch (\Exception $e) {
                    return ['error' => 'error', 'message' => 'Get balance error: ' . $e->getMessage()];
                }
                break;
            default:
                return ['error' => 'undefined action', 'message' => "Action {$actionName} not supported!"];
        }
    }

    /**
     * Return denomination coefficient
     * @return int
     */
    public function getDenomination(): int
    {
        return $this->config->getDenomination();
    }
}
