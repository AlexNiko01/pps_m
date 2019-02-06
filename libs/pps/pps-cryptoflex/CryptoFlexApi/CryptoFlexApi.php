<?php

/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 16.07.18
 * Time: 16:10
 */

namespace pps\cryptoflex\CryptoFlexApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use pps\cryptoflex\CryptoFlexApi\Callbacks\OrderCallback;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoWalletBalanceResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoWalletCreateResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\NullResponse;
use pps\payment\Payment;
use pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest;
use pps\cryptoflex\CryptoFlexApi\Requests\CryptoFlexRequestInterface;
use pps\cryptoflex\CryptoFlexApi\Responses\BaseResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\CryptoFlexResponseInterface;
use Yii;
use yii\base\Model;

/**
 * @property CryptoFlexConfig $config
 * @property Exception $exception
 * @property integer $errorCode
 * @property string requestUrl
 */
class CryptoFlexApi extends Model
{
    /** Statuses of server response*/
    const STATUS_SUCCESS = 200;
    // some error comes from provider side with JSON object - try send transaction later
    const STATUS_ERROR_PROVIDER = 100;

    //connection problems
    const STATUS_ERROR_CONNECT = 300;
    // error on client side
    const STATUS_ERROR_CLIENT = 400;
    // error on server side
    const STATUS_ERROR_SERVER = 500;
    // unexpected request error
    const STATUS_ERROR_REQUEST = 520;
    // unexpected error
    const STATUS_ERROR_UNDEFINED = 600;

    public $config;

    private $errorCode;

    private $exception;

    private $rawRequestToProvider = [];
    private $rawResponseFromProvider = [];
    private $requestUrl;

    public $lastErrorMessage;
    /**
     * Global payment timeout for curl option
     * @var int
     */
    public $timeout = 7;
    /**
     * Global payment connect timeout for curl option
     * @var int
     */
    public $connect_timeout = 5;

    public function __construct(CryptoFlexConfig $conf, array $config = [])
    {
        $this->config = $conf;
        parent::__construct($config);
    }

    /**
     * @param array $data
     * @param string $paymentMethod
     * @return CryptoFlexResponseInterface
     * @throws Exceptions\CryptoFlexValidationException
     * @throws GuzzleException
     */
    public function getAddress(array $data, string $paymentMethod): CryptoFlexResponseInterface
    {
        return $this->executeRequest(
            $data,
            $paymentMethod,
            CryptoFlexConfig::API_MODE_ORDER,
            'Create order error:'
        );
    }

    /**
     * @param array $data
     * @param string $paymentMethod
     * @return NullResponse|CryptoFlexResponseInterface
     * @throws Exceptions\CryptoFlexValidationException
     * @throws GuzzleException
     */
    public function createWithdrawal(array $data, string $paymentMethod)
    {
        return $this->executeRequest(
            $data,
            $paymentMethod,
            CryptoFlexConfig::API_MODE_PAYOUT,
            'Create payout error:'
        );
    }

    /**
     * @param string $currency
     * @param string $transactionId
     * @param string $walletAddress
     * @return CryptoFlexResponseInterface
     * @throws Exceptions\CryptoFlexValidationException
     * @throws GuzzleException
     */
    public function getWithdrawalStatus(
        string $currency,
        string $transactionId,
        string $walletAddress
    ): CryptoFlexResponseInterface {
        $data = [
            'crypto_currency' => $currency,
            'partner_withdraw_id' => $transactionId,
            'wallet_address' => $walletAddress
        ];
        return $this->executeRequest(
            $data,
            CryptoFlexPaymentMethod::CRYPTO,
            CryptoFlexConfig::API_MODE_PAYOUT_STATUS,
            'Create transaction status error:'
        );
    }

    /**
     * @param string $currency
     * @return CryptoWalletCreateResponse
     * @throws GuzzleException
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function createWallet(string $currency): CryptoFlexResponseInterface
    {
        return $this->executeRequest(
            ['crypto_currency' => $currency, 'partner_id' => $this->config->getPartnerId()],
            CryptoFlexPaymentMethod::CRYPTO,
            CryptoFlexConfig::API_MODE_WALLET_CREATE,
            'Create wallet error:'
        );
    }

    /**
     * @param string $currency
     * @param string $walletAddress
     * @return CryptoWalletBalanceResponse
     * @throws GuzzleException
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function getWalletBalance(string $currency, string $walletAddress): CryptoFlexResponseInterface
    {
        return $this->executeRequest(
            ['crypto_currency' => $currency, 'wallet_address' => $walletAddress],
            CryptoFlexPaymentMethod::CRYPTO,
            CryptoFlexConfig::API_MODE_WALLET_BALANCE,
            'Create wallet error:'
        );
    }

    /**
     * @param string $paymentMethod
     * @param string $apiMode
     * @param array $data
     * @return bool
     */
    public function checkRequestFields(string $paymentMethod, string $apiMode, array $data): bool
    {
        try {
            $this->config->setPaymentMethod($paymentMethod);
            $this->config->setApiMode($apiMode);
            BaseRequest::getRequest($this->config, $data);
        } catch (CryptoFlexAPIException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param $receive_data
     * @return bool|OrderCallback
     */
    public function getCallback($receive_data)
    {
        try {
            $callback = new OrderCallback($this->config, $receive_data);
            $this->errorCode = self::STATUS_SUCCESS;
            return $callback;
        } catch (CryptoFlexAPIException $e) {
            $this->errorLog($e->getMessage(), $e);
            $this->errorCode = $e->getCode();
            return false;
        }
    }

    /**
     * @param string $statusFromCallback
     * @return int
     */
    public function convertWithdrawalStatus(string $statusFromCallback): int
    {
        switch ($statusFromCallback) {
            case CryptoFlexStatuses::STATUS_WITHDRAW_NEW:
            case CryptoFlexStatuses::STATUS_WITHDRAW_IN_PROCESS:
            case CryptoFlexStatuses::STATUS_WITHDRAW_INTERNAL_ERROR:
                $transactionStatus = Payment::STATUS_PENDING;
                break;
            case CryptoFlexStatuses::STATUS_WITHDRAW_SUCCESSFUL:
                $transactionStatus = Payment::STATUS_SUCCESS;
                break;
            case CryptoFlexStatuses::STATUS_WITHDRAW_REJECTED:
            case CryptoFlexStatuses::STATUS_WITHDRAW_CRYPTO_FLEX_REJECTED:
                $transactionStatus = Payment::STATUS_ERROR;
                break;
            default:
                $transactionStatus = Payment::STATUS_PENDING;
                break;
        }
        return $transactionStatus;
    }

    public function convertDepositStatus(string $statusFromCallback): int
    {
        switch ($statusFromCallback) {
            case CryptoFlexStatuses::STATUS_INVOICE_PAID:
                $transactionStatus = Payment::STATUS_PENDING;
                break;
            case CryptoFlexStatuses::STATUS_INVOICE_SUCCESSFUL:
                $transactionStatus = Payment::STATUS_SUCCESS;
                break;
            case CryptoFlexStatuses::STATUS_INVOICE_REJECTED:
                $transactionStatus = Payment::STATUS_ERROR;
                break;
            default:
                $transactionStatus = Payment::STATUS_PENDING;
                break;
        }
        return $transactionStatus;
    }

    /**
     * Checking final status for withdrawal
     * @param int $status
     * @return boolean
     */
    public static function isFinalWithdrawal(int $status): bool
    {
        return CryptoFlexStatuses::isFinalWithdrawStatus($status);
    }

    /**
     * Checking final status for deposit
     * @param int $status
     * @return boolean
     */
    public static function isFinalDeposit(int $status): bool
    {
        return CryptoFlexStatuses::isFinalDepositStatus($status);
    }

    /**
     * @param int $status
     * @return bool
     */
    public function isSuccessWithdrawal(int $status): bool
    {
        return $status === CryptoFlexStatuses::STATUS_WITHDRAW_SUCCESSFUL;
    }

    /**
     * @param int $status
     * @return bool
     */
    public function isSuccessDeposit(int $status): bool
    {
        return $status === CryptoFlexStatuses::STATUS_INVOICE_SUCCESSFUL;
    }

    /**
     * @param CryptoFlexRequestInterface $request
     * @return CryptoFlexResponseInterface
     * @throws GuzzleException
     * @throws Exceptions\CryptoFlexValidationException
     */
    protected function apiCall(CryptoFlexRequestInterface $request): CryptoFlexResponseInterface
    {
        $this->setRawRequest($request->getRequestBody());
        $this->requestUrl = $request->getRequestUrl();

        /**
         * @var Client $httpClient
         */
        try {
            $httpClient = Yii::$container->get(
                Client::class,
                [
                    'connect_timeout' => $this->connect_timeout,
                    'timeout' => $this->timeout,
                ]
            );
            $requestData = $request->prepareRequest();
            $response = $httpClient->send($requestData);
            $data = $response->getBody();

            if ($data) {
                $this->setRawResponse((string) $data);
                $responseArray = $this->getRawResponse();
                $responseObject = BaseResponse::getResponse($this->config, $responseArray);
                $this->errorCode = self::STATUS_SUCCESS;
                if ($responseObject->error_code) {
                    $this->errorLog('Error from provider ' . __METHOD__ . ': ' . $responseObject->message);
                    $this->errorCode = self::STATUS_ERROR_PROVIDER;
                }
                return $responseObject->data;
            }
            $this->errorLog('Object not found ' . __METHOD__ . ': ' . $response->getBody());
            $this->errorCode = self::STATUS_ERROR_PROVIDER;
        } catch (ConnectException $e) {
            $this->errorLog(__METHOD__ . ' error: ', $e);
            $this->errorCode = self::STATUS_ERROR_CONNECT;
        } catch (ClientException $e) {
            $this->errorLog(__METHOD__ . ' error: ', $e);
            $this->errorCode = self::STATUS_ERROR_CLIENT;
        } catch (ServerException $e) {
            $this->errorLog(__METHOD__ . ' error: ', $e);
            $this->errorCode = self::STATUS_ERROR_SERVER;
        } catch (RequestException $e) {
            $this->errorLog(__METHOD__ . ' error: ', $e);
            $this->errorCode = self::STATUS_ERROR_REQUEST;
        } catch (Exception $e) {
            $this->errorLog(__METHOD__ . ' error: ', $e);
            $this->errorCode = self::STATUS_ERROR_UNDEFINED;
        }
        return new NullResponse($this->config);
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @param $message
     * @param Exception|null $e
     */
    private function errorLog($message, Exception $e = null)
    {
        $this->exception = $e;
        if ($e !== null) {
            $message .= "\n {$e->getMessage()}";
        }
        $this->lastErrorMessage = $message;
        Yii::error($this->lastErrorMessage, 'payment-cryptoflex');
    }

    /**
     * @param $request
     */
    protected function setRawRequest($request)
    {
        $this->rawRequestToProvider = $request;
    }

    /**
     * @return Exception|null
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return array
     */
    public function getRawRequest(): array
    {
        return $this->rawRequestToProvider;
    }

    /**
     * @param $apiCall
     */
    private function setRawResponse($apiCall)
    {
        $this->rawResponseFromProvider = json_decode($apiCall, true) ?? [];
    }

    /**
     * @return array
     */
    public function getRawResponse(): array
    {
        return $this->rawResponseFromProvider;
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @param array $data
     * @param string $paymentMethod
     * @param string $apiMode
     * @param string $errorString
     * @return CryptoFlexResponseInterface
     * @throws Exceptions\CryptoFlexValidationException
     * @throws GuzzleException
     */
    protected function executeRequest(array $data, string $paymentMethod, string $apiMode, string $errorString)
    {
        try {
            $this->config->setPaymentMethod($paymentMethod);
            $this->config->setApiMode($apiMode);
            return $this->apiCall(BaseRequest::getRequest($this->config, $data));
        } catch (Exception $e) {
            $this->errorLog($errorString, $e);
            $this->errorCode = $e->getCode();
            return new NullResponse($this->config);
        }
    }
}
