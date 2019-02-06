<?php

namespace pps\zotapay;

use common\models\Transaction;
use console\jobs\UpdateStatusJob;
use pps\zotapay\ZotaPayApi\Callbacks\BaseCallback;
use pps\zotapay\ZotaPayApi\Responses\BaseResponse;
use pps\zotapay\ZotaPayApi\Responses\card\CardOrderResponse;
use pps\zotapay\ZotaPayApi\Responses\card\CardPayoutResponse;
use pps\zotapay\ZotaPayApi\Responses\ZotaPayResponseInterface;
use pps\zotapay\ZotaPayApi\ZotaPayApi;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayItemNotExistException;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\ZotaPayEndpoints;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;
use pps\zotapay\ZotaPayApi\Currencies\ZotaPayCurrency;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayValidationException;
use pps\zotapay\ZotaPayApi\ZotaPayStatuses;
use Yii;
use pps\payment\Payment;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Response;

/**
 * Class ZotaPay
 * @package pps\zotapay
 */
class ZotaPay extends Payment
{

    /**@var ZotaPayConfig $config */
    private $config;

    /**@var ZotaPayApi $cardPayApi */
    private $cardPayApi;

    /**
     * Fill the class with the necessary parameters
     * @param array $data
     */
    public function fillCredentials(array $data)
    {
        try {
            if (empty($data['controlKey'])) {
                throw new ZotaPayValidationException('Control Key empty');
            }
            if (empty($data['login'])) {
                throw new ZotaPayValidationException('Login empty');
            }

            $this->config = new ZotaPayConfig(
                ZotaPayPaymentMethod::CARD,
                $data['sandbox'] ? ZotaPayEndpoints::TEST : ZotaPayEndpoints::LIVE
            );
            $this->config->setControlKey($data['controlKey']);
            $this->config->setClientLogin($data['login']);

            if (!empty($data['login'])) {
                $this->config->setEndpointId($data['endpointId']);
            }
        } catch (ZotaPayAPIException $e) {
            $this->logAndDie(
                'Required parameters of config is incorrect',
                $e->getMessage(),
                'ZotaPay fill credentials',
                'zotapay-receive'
            );
        }
        $this->cardPayApi = new ZotaPayApi($this->config);
    }

    /**
     * Preliminary calculation of the invoice.
     * Get required fields for invoice.
     * @param array $params
     * @return array
     */
    public function preInvoice(array $params): array
    {

        if (!empty($params['commission_payer'])) {
            return self::prepareErrorReturn(self::ERROR_COMMISSION_PAYER);
        }

        try {
            static::validateTransaction(
                $params['currency'],
                $params['payment_method'],
                $params['amount'],
                self::WAY_DEPOSIT
            );
        } catch (ZotaPayAPIException $e) {
            return self::prepareErrorReturn($e->getMessage());
        }

        return [
            'data' => [
                'fields' => static::getFields($params['currency'], $params['payment_method'], self::WAY_DEPOSIT),
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function invoice(array $params): array
    {
        /**
         * @var Transaction $transaction
         *
         */
        $transaction = $params['transaction'];
        $requests = $params['requests'];

        try {
            static::validateTransaction(
                $transaction->currency,
                $transaction->payment_method,
                $transaction->amount,
                self::WAY_DEPOSIT
            );
        } catch (ZotaPayAPIException $e) {
            return self::prepareErrorReturn($e->getMessage());
        }

        $transaction->save(false);

        if (!empty($params['commission_payer'])) {
            return self::prepareErrorReturn(self::ERROR_COMMISSION_PAYER);
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);
        /*
                // Added transaction_id for redirection
                $params['success_url'] = $params['success_url']
                    . (strpos($params['success_url'], '?') ? '&' : '?') . "n={$transaction->id}";
                $params['fail_url'] = $params['fail_url']
                    . (strpos($params['fail_url'], '?') ? '&' : '?') . "n={$transaction->id}";
        */
        $requisites = json_decode($transaction->requisites, true);

        try {
            self::checkRequisites(
                $requisites,
                $transaction->currency,
                $transaction->payment_method,
                self::WAY_DEPOSIT
            );
        } catch (ZotaPayItemNotExistException $e) {
            return self::prepareErrorReturn($e->getMessage());
        }

        $urls = json_decode($transaction->urls);

        $callbackUrl = ArrayHelper::getValue($urls, 'callback_url', '');
        if ($callbackUrl === '') {
            $callbackUrl = ArrayHelper::getValue($params, 'callback_url', '');
        }

        $allOrderData = [
            'client_orderid' => $transaction->id,
            'order_desc' => $transaction->comment,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'redirect_url' => Url::to(['redirect/redirect/' . 'zotapay?n=' . $transaction->id], true),
            'server_callback_url' => $callbackUrl,
            'requisites' => $requisites
        ];

        /**@var CardOrderResponse $response */
        $response = $this->cardPayApi->createOrder($allOrderData, $transaction->payment_method);

        $transaction->query_data = json_encode($this->cardPayApi->getRawRequest()); //в виде массива
        $transaction->result_data = $this->cardPayApi->getRawResponse() == []
            ? json_encode(['error' => $this->cardPayApi->lastErrorMessage])
            : json_encode($this->cardPayApi->getRawResponse());

        $this->logger->log($transaction->id, 2, $transaction->query_data);
        $this->logger->log($transaction->id, 3, $transaction->result_data);
        $transaction->save(false);
        /*
                if ($this->cardPayApi->getErrorCode() !== ZotaPayApi::STATUS_SUCCESS) {
                    return self::prepareErrorReturn($this->cardPayApi->lastErrorMessage);
                }
        */
        return $this->handlResponseToPPS(
            $response,
            $transaction,
            function (Transaction $transaction, CardOrderResponse $response) {
                Yii::info(
                    ['query' => $transaction->query_data, 'result' => $transaction->result_data],
                    'payment-zotapay-invoice'
                );
                // for creating of status transaction should use serial_number from order response
                $transaction->external_id = $response->paynet_order_id . '?' . $response->serial_number;
                $transaction->refund = $transaction->amount;
                $transaction->write_off = $transaction->amount;
                $transaction->status = Payment::STATUS_CREATED;
                $transaction->save(false);

                $answer = [
                    'redirect' => [
                        'method' => 'POST',
                        'url' => $response->redirect_url,
                        'params' => [],
                    ],
                    'data' => $transaction::getDepositAnswer($transaction)
                ];

                $statusJob = new UpdateStatusJob();
                $statusJob->transaction_id = $transaction->id;
                Yii::$app->queue->push($statusJob);

                return $answer;
            },
            true
        );
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

        if (in_array($transaction->status, self::getFinalStatuses(), true)) {
            return true;
        }
        /** @var BaseCallback $receive */
        $receive = $this->cardPayApi->getCallback($data['receive_data']);
        if (!$receive) {
            $this->logAndDie(
                "ZotaPay receive() error ({$transaction->id})",
                $this->cardPayApi->lastErrorMessage,
                'ZotaPay callback',
                'ZotaPay-receive'
            );
        };
        if ((float)$transaction->amount != (float)$receive->amount) {
            $this->logAndDie(
                "ZotaPay receive() transaction amount not equal received amount ({$transaction->id})",
                "Transaction amount = {$transaction->amount}\nreceived amount = {$receive->amount}",
                'Transaction amount not equal received amount'
            );
        }
        /*
                // If different currency
                if ($transaction->currency != $receive->currency) {
                    $this->logAndDie(
                        "Cardpay receive() different currency ({$transaction->id})",
                        "Merchant currency = {$transaction->currency}\nreceived currency = {$receive->currency}",
                        "Different currency",
                        'cardpay-receive'
                    );
                }

        if (empty($transaction->external_id)) {
            $transaction->external_id = $receive->orderid;
        }
        */
        $transactionStatus = $this->cardPayApi->convertPsTransactionStatusToPpsStatus($receive->status);
        $transaction->status = $transactionStatus;
        if ($transactionStatus === Payment::STATUS_SUCCESS) {
            $this->fillTransactionCallbackData($transaction, $receive);
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

        if (!empty($params['commission_payer'])) {
            return self::prepareErrorReturn(self::ERROR_COMMISSION_PAYER);
        }

        try {
            static::validateTransaction(
                $params['currency'],
                $params['payment_method'],
                $params['amount'],
                self::WAY_WITHDRAW
            );
        } catch (ZotaPayAPIException $e) {
            return self::prepareErrorReturn($e->getMessage());
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function withDraw(array $params): array
    {
        /** @var Transaction $transaction */
        $transaction = $params['transaction'];

        $requests = $params['requests'];

        try {
            static::validateTransaction(
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
        } catch (ZotaPayAPIException $e) {
            return $this->prepareFinalTransactionError($transaction, $e->getMessage());
        }

        $transaction->status = self::STATUS_CREATED;
        $transaction->save(false);

        if (!empty($params['commission_payer'])) {
            return $this->prepareFinalTransactionError($transaction, self::ERROR_COMMISSION_PAYER);
        }

        $this->logger->log($transaction->id, 1, $requests['merchant']);
        /*
                if ($transaction->payment_method == ZotaPayPaymentMethod::CARD) {
                    $cardNumber = CardToken::hideCard($requisites['number']);
                    $cardToken = CardToken::getToken($transaction->brand_id, $transaction->buyer_id, $cardNumber);
                    $requisites['cardToken'] = $cardToken->token ?? '';
                    if (!$cardToken) {
                        return $this->prepareFinalTransactionError($transaction, 'card_token not found!');
                    }
                }
        */
        $urls = json_decode($transaction->urls);

        $callbackUrl = ArrayHelper::getValue($urls, 'callback_url', '');
        if ($callbackUrl === '') {
            $callbackUrl = ArrayHelper::getValue($params, 'callback_url', '');
        }

        $queryData = [
            'client_orderid' => $transaction->id,
            'amount' => (float)$transaction->amount,
            'currency' => $transaction->currency,
            'description' => $transaction->comment,
            'requisites' => $requisites,
            'server_callback_url' => $callbackUrl,
        ];

        /**@var CardPayoutResponse $response */
        $response = $this->cardPayApi->createWithdrawal($queryData, $transaction->payment_method);

        $transaction->query_data = json_encode($this->cardPayApi->getRawRequest()); //в виде массива
        $transaction->result_data = $this->cardPayApi->getRawResponse() == []
            ? json_encode(['error' => $this->cardPayApi->lastErrorMessage])
            : json_encode($this->cardPayApi->getRawResponse());

        $this->logger->log($transaction->id, 2, $transaction->query_data);
        $this->logger->log($transaction->id, 3, $transaction->result_data);
        $transaction->save(false);
        /*
        try {
            $this->cardPayApi->checkWithdrawalRequestFields($transaction->payment_method, $queryData);
        } catch (ZotaPayAPIException $e) {
            return $this->prepareFinalTransactionError($transaction, $e->getMessage());
        }

        Yii::info(
            [
                'query' => $this->cardPayApi->getRawRequest(),
                'result' => $this->cardPayApi->getRawResponse()
            ]
        );

        $answer = [];


        switch ($this->cardPayApi->errorCode) {
            case ZotaPayApi::STATUS_SUCCESS:
                $transaction->external_id = $apiResponse->id;
                $transaction->receive = $transaction->amount;
                $transaction->write_off = $transaction->amount;

                $transaction->status = $this->cardPayApi->getWdwTrStatusByResp($apiResponse->state);
                $transaction->save(false);

                if (!$this->cardPayApi::isFinalTransaction($apiResponse->state)) {
                    $params['updateStatusJob']->transaction_id = $transaction->id;
                }
                $answer['data'] = $transaction::getWithdrawAnswer($transaction);
                return $answer;
                break;
            case ZotaPayApi::STATUS_ERROR_CONNECT:
                $transaction->status = self::STATUS_TIMEOUT;
                break;
            case ZotaPayApi::STATUS_ERROR_PROVIDER:
            case ZotaPayApi::STATUS_ERROR_CLIENT:
                return $this->prepareFinalTransactionError($transaction, $this->cardPayApi->lastErrorMessage);
            case ZotaPayApi::STATUS_ERROR_SERVER:
            case ZotaPayApi::STATUS_ERROR_REQUEST:
                $transaction->status = self::STATUS_NETWORK_ERROR;
                $transaction->result_data = json_encode(
                    ['error_message' => $this->cardPayApi->exception->getMessage()]
                );
                break;
            case ZotaPayApi::STATUS_ERROR_UNDEFINED:
                $answer['message'] = self::ERROR_OCCURRED;
                $answer['status'] = 'error';
                $message = "Request url = '" . $this->cardPayApi->getRequestUrl();
                $message .= "\nRequest result = " . $this->cardPayApi->getRawResponse() ?? '';
                Yii::error($message, 'payment-cardpay-withdraw');
                return $answer;
                break;
            default:
                $transaction->status = Payment::STATUS_ERROR;
                break;
        }

        $transaction->save(false);
        $params['updateStatusJob']->transaction_id = $transaction->id;
        $answer['data'] = $transaction::getWithdrawAnswer($transaction);
        return $answer;
        */
        $params['updateStatusJob']->transaction_id = $transaction->id;

        return $this->handlResponseToPPS(
            $response,
            $transaction,
            function (Transaction $transaction, BaseResponse $response) use ($params) {
                $transaction->external_id = $response->paynet_order_id . '?' . $response->serial_number;
                $transaction->receive = $transaction->amount;
                $transaction->write_off = $transaction->amount;
                $transaction->save(false);
                $params['updateStatusJob']->transaction_id = $transaction->id;
                $answer['data'] = $transaction::getWithdrawAnswer($transaction);
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateStatus($transaction, $model_req = null)
    {
        if (!in_array($transaction->status, self::getNotFinalStatuses())) {
            return false;
        }
        if (!$transaction->external_id) {
            return false;
        }

        /**
         * В поле external_id хранится externalId и by_request_sn разделенные знаком '?'
         */
        $providerTransactionIdentifiersArray = explode('?', $transaction->external_id);
        /** @var BaseResponse $status */
        $status = $this->cardPayApi->getTransactionStatus(
            $transaction->way,
            $transaction->id,
            $providerTransactionIdentifiersArray[0] ?? null,
            $providerTransactionIdentifiersArray[1] ?? null
        );

        switch ($this->cardPayApi->errorCode) {
            case ZotaPayApi::STATUS_SUCCESS:
            case ZotaPayApi::STATUS_ERROR_PROVIDER:
                if (empty($transaction->external_id)) {
                    $transaction->external_id = $status->paynet_order_id;
                    $transaction->save(false);
                }
                if (isset($model_req)) {
                    $this->logger->log($transaction->id, 8, $status->getResponseBody());
                }
                Yii::info($status->getResponseBody(), 'payment-zotapay-status');
                $transactionStatus = $this->cardPayApi->convertPsTransactionStatusToPpsStatus($status->status);
                $transaction->status = $transactionStatus;
                if ($transactionStatus === Payment::STATUS_SUCCESS) {
                    $this->fillTransactionCallbackData($transaction, $status);
                }
                $transaction->save(false);
                return true;
            case ZotaPayApi::STATUS_ERROR_CONNECT:
            case ZotaPayApi::STATUS_ERROR_CLIENT:
            case ZotaPayApi::STATUS_ERROR_SERVER:
            case ZotaPayApi::STATUS_ERROR_REQUEST:
            case ZotaPayApi::STATUS_ERROR_UNDEFINED:
                Yii::error($this->cardPayApi->lastErrorMessage, 'payment-zotapay-status');
                Yii::error($this->cardPayApi->getRawRequest(), 'payment-zotapay-status');
                Yii::error($this->cardPayApi->getRawResponse(), 'payment-zotapay-status');
        }
        return false;
    }

    /**
     * @param Transaction $transaction
     * @param $response
     */
    private function fillTransactionCallbackData(Transaction $transaction, $response)
    {
        if ($transaction->way === Payment::WAY_DEPOSIT && is_object($response)) {
            $callback_array = [];
            if (property_exists($response, 'bin') && $response->bin !== '') {
                $callback_array[$transaction->payment_method]['number'] = $response->bin;
            }
            if (property_exists($response, 'card_type') && $response->card_type !== '') {
                $callback_array[$transaction->payment_method]['card_type'] = $response->card_type;
            }
            if (property_exists($response, 'name') && $response->name !== '') {
                $callback_array[$transaction->payment_method]['name'] = $response->name;
            }
            if (property_exists($response, 'cardholder_name') && $response->cardholder_name !== '') {
                $callback_array[$transaction->payment_method]['cardholder_name'] = $response->cardholder_name;
            }
            $transaction->callback_data = json_encode($callback_array);
        }
    }

    /**
     * Get model for validation incoming data
     * @return Model
     */
    public static function getModel(): Model
    {
        return new \pps\zotapay\Model();
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
        return ZotaPayApi::getTransactionId($data);
    }

    /**
     * @param array $data
     * @return string
     */
    public static function getPaymentResult(array $data): string
    {
        $status = ArrayHelper::getValue($data, 'status', '');
        if (ZotaPayStatuses::isSuccessStatus($status)) {
            return 'success';
        }
        if (ZotaPayStatuses::isFinalErrorStatus($status)) {
            return 'error';
        }
        return 'pending';
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
     * Validation transaction before send to payment system
     * @param string $currency
     * @param string $paymentMethod
     * @param float $amount
     * @param string $way
     * @return bool|string
     * @throws ZotaPayItemNotExistException
     * @throws ZotaPayValidationException
     */
    public static function validateTransaction(string $currency, string $paymentMethod, float $amount, string $way)
    {
        $currencies = ZotaPayCurrency::getCurrencyClass($paymentMethod);
        $currencies->isSupportedWay($way);
        $currencies->isSupportedCurrency($currency);
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
            return ZotaPayCurrency::getCurrencyClass($paymentMethod)->getFields($currency, $way);
        } catch (ZotaPayItemNotExistException $e) {
            return [];
        }
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        try {
            return ZotaPayCurrency::getAllApiFields();
        } catch (ZotaPayItemNotExistException $e) {
            return [];
        }
    }

    /**
     * Get supported currencies
     * @return array
     * @throws ZotaPayItemNotExistException
     */
    public static function getSupportedCurrencies(): array
    {
        return ZotaPayCurrency::getAllSupportedCurrencies();
    }

    /**
     * @param array $requisites
     * @param string $currency
     * @param string $method
     * @param string $way
     * @return bool|string
     * @throws ZotaPayItemNotExistException
     */
    private static function checkRequisites(array $requisites, string $currency, string $method, string $way)
    {
        $fields = ZotaPayCurrency::getCurrencyClass($method)->getFields($currency, $way);
        foreach ($fields as $fieldName => $field) {
            if (!array_key_exists($fieldName, $requisites)
                && ArrayHelper::getValue($fields, [$fieldName, 'required'], false)) {
                throw new ZotaPayItemNotExistException("Required param '{$fieldName}' not found");
            }
        }
        return true;
    }


    private static function prepareErrorReturn($message)
    {
        return [
            'status' => 'error',
            'message' => $message
        ];
    }

    /**
     * @param Transaction $transaction
     * @param $message
     * @return array
     */
    private function prepareFinalTransactionError(Transaction $transaction, $message): array
    {
        $transaction->status = self::STATUS_ERROR;
        $transaction->result_data = json_encode(['error' => $message]);
        $transaction->save(false);
        return self::prepareErrorReturn($message);
    }

    /**
     * @param ZotaPayResponseInterface $response
     * @param Transaction $transaction
     * @param callable $successFoo
     * @param bool $updateJobStart
     * @return array
     */
    private function handlResponseToPPS(
        ZotaPayResponseInterface $response,
        Transaction $transaction,
        callable $successFoo,
        bool $updateJobStart = false
    ): array {
        switch ($this->cardPayApi->errorCode) {
            case ZotaPayApi::STATUS_SUCCESS:
                return $successFoo($transaction, $response);
            case ZotaPayApi::STATUS_ERROR_CONNECT:
                $transaction->status = self::STATUS_TIMEOUT;
                break;
            case ZotaPayApi::STATUS_ERROR_PROVIDER:
            case ZotaPayApi::STATUS_ERROR_CLIENT:
                return $this->prepareFinalTransactionError($transaction, $this->cardPayApi->lastErrorMessage);
            case ZotaPayApi::STATUS_ERROR_SERVER:
            case ZotaPayApi::STATUS_ERROR_REQUEST:
                $transaction->status = self::STATUS_NETWORK_ERROR;
                $transaction->result_data = json_encode(
                    ['error_message' => $this->cardPayApi->exception->getMessage()]
                );
                break;
            case ZotaPayApi::STATUS_ERROR_UNDEFINED:
                $answer['message'] = self::ERROR_OCCURRED;
                $answer['status'] = 'error';
                $message = "Request url = '" . $this->cardPayApi->getRequestUrl();
                $message .= "\nRequest result = " . $this->cardPayApi->getRawResponse() ?? '';
                Yii::error($message, 'payment-zotapay-transaction');
                return $answer;
                break;
            default:
                $transaction->status = Payment::STATUS_ERROR;
                break;
        }
        $transaction->save(false);
        $answer['data'] = $transaction->way == Payment::WAY_DEPOSIT
            ? $transaction::getDepositAnswer($transaction)
            : $transaction::getWithdrawAnswer($transaction);

        if ($updateJobStart) {
            $statusJob = new UpdateStatusJob();
            $statusJob->transaction_id = $transaction->id;
            Yii::$app->queue->push($statusJob);
        }
        return $answer;
    }
}
