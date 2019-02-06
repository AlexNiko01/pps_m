<?php

/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 16.07.18
 * Time: 16:10
 */

namespace pps\zotapay\ZotaPayApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use function GuzzleHttp\Psr7\parse_query;
use pps\zotapay\ZotaPayApi\Callbacks\BaseCallback;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;
use pps\payment\Payment;
use pps\zotapay\ZotaPayApi\Requests\BaseRequest;
use pps\zotapay\ZotaPayApi\Requests\ZotaPayRequestInterface;
use pps\zotapay\ZotaPayApi\Responses\BaseResponse;
use pps\zotapay\ZotaPayApi\Responses\NullResponse;
use pps\zotapay\ZotaPayApi\Responses\ZotaPayResponseInterface;
use Yii;
use yii\base\Model;

/**
 * @property ZotaPayConfig $config
 * @property Exception $exception
 * @property integer $errorCode
 * @property string requestUrl
 */
class ZotaPayApi extends Model
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

    public function __construct(ZotaPayConfig $conf, array $config = [])
    {
        $this->config = $conf;
        parent::__construct($config);
    }

    /**
     * @param array $data
     * @param string $paymentMethod
     * @return ZotaPayResponseInterface
     * @throws GuzzleException
     */
    public function createOrder(array $data, string $paymentMethod)
    {
        try {
            $this->config->setPaymentMethod($paymentMethod);
            $this->config->setApiMode(ZotaPayConfig::API_MODE_ORDER);
            return $this->apiCall(BaseRequest::getRequest($this->config, $data));
        } catch (Exception $e) {
            $this->errorLog('Create order error:', $e);
            $this->errorCode = $e->getCode();
            return new NullResponse($this->config);
        }
    }

    /**
     * @param array $data
     * @param string $paymentMethod
     * @return NullResponse|ZotaPayResponseInterface
     * @throws GuzzleException
     */
    public function createWithdrawal(array $data, string $paymentMethod)
    {
        try {
            $this->config->setPaymentMethod($paymentMethod);
            $this->config->setApiMode(ZotaPayConfig::API_MODE_PAYOUT);
            return $this->apiCall(BaseRequest::getRequest($this->config, $data));
        } catch (Exception $e) {
            $this->errorLog('Create payout error:', $e);
            $this->errorCode = $e->getCode();
            return new NullResponse($this->config);
        }
    }

    /**
     * @param string $way
     * @param string $transactionId
     * @param string $externalId
     * @param string $by_request_sn
     * @return ZotaPayResponseInterface
     * @throws GuzzleException
     */
    public function getTransactionStatus(string $way, string $transactionId, string $externalId, string $by_request_sn)
    {
        $data = [
            'client_orderid' => $transactionId,
            'orderid' => $externalId,
            'by_request_sn' => $by_request_sn
        ];
        try {
            $this->config->setApiMode(
                $way === Payment::WAY_DEPOSIT
                    ? ZotaPayConfig::API_MODE_ORDER_STATUS
                    : ZotaPayConfig::API_MODE_PAYOUT_STATUS
            );
            return $this->apiCall(BaseRequest::getRequest($this->config, $data));
        } catch (Exception $e) {
            $this->errorLog('Create transaction status error:', $e);
            $this->errorCode = $e->getCode();
            return new NullResponse($this->config);
        }
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
        } catch (ZotaPayAPIException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param $receive_data
     * @return bool|BaseCallback
     */
    public function getCallback($receive_data)
    {
        try {
            return new BaseCallback($this->config, $receive_data);
        } catch (ZotaPayAPIException $e) {
            $this->errorLog($e->getMessage());
            $this->errorCode = $e->getCode();
            return false;
        }
    }

    /**
     * @param string $statusFromCallback
     * @return int
     */
    public function convertPsTransactionStatusToPpsStatus(string $statusFromCallback): int
    {
        switch ($statusFromCallback) {
            case ZotaPayStatuses::STATUS_TRANSACTION_PROCESSING:
            case ZotaPayStatuses::STATUS_TRANSACTION_UNKNOWN:
                $transactionStatus = Payment::STATUS_PENDING;
                break;
            case ZotaPayStatuses::STATUS_TRANSACTION_APPROVED:
                $transactionStatus = Payment::STATUS_SUCCESS;
                break;
            case ZotaPayStatuses::STATUS_TRANSACTION_FILTERED:
            case ZotaPayStatuses::STATUS_TRANSACTION_ERROR:
            case ZotaPayStatuses::STATUS_TRANSACTION_DECLINED:
                $transactionStatus = Payment::STATUS_ERROR;
                break;
            default:
                $transactionStatus = Payment::STATUS_PENDING;
                break;
        }
        return $transactionStatus;
    }

    /**
     * Checking final status for deposit
     * @param string $status
     * @return boolean
     */
    public static function isFinalTransaction(string $status): bool
    {
        return \in_array($status, ZotaPayStatuses::getFinalStatuses(), true);
    }

    /**
     * Checking success status for deposit
     * @param string $status
     * @return bool
     */
    public function isSuccessTransaction(string $status): bool
    {
        return $status === ZotaPayStatuses::STATUS_TRANSACTION_APPROVED;
    }

    /**
     * @param ZotaPayRequestInterface $request
     * @return ZotaPayResponseInterface
     * @throws GuzzleException
     */
    protected function apiCall(ZotaPayRequestInterface $request): ZotaPayResponseInterface
    {
        $this->setRawRequest($request->getRequestBody());
        $this->requestUrl = $request->getRequestUrl();

        /**
         * @var Client $httpClient
        */
        try {
            $httpClient = Yii::$container->get(Client::class, [
                'connect_timeout' => $this->connect_timeout,
                'timeout' => $this->timeout,
            ]);
            $response = $httpClient->send($request->prepareRequest());
            $data = (string) $response->getBody();

            if ($data) {
                $this->setRawResponse($data);
                $responseArray = \is_string($data) ? parse_query($data) : [];
                $responseObject = BaseResponse::getResponse($this->config, $responseArray);
                $this->errorCode = self::STATUS_SUCCESS;
                if ($responseObject->error_code) {
                    $this->errorLog('Error from provider ' . __METHOD__ . ': ' . $responseObject->error_message);
                    $this->errorCode = self::STATUS_ERROR_PROVIDER;
                }
                return $responseObject;
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

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    private function errorLog($message, Exception $e = null)
    {
        $this->exception = $e;
        if ($e !== null) {
            $message .= "\n {$e->getMessage()}";
        }
        $this->lastErrorMessage = $message;
        Yii::error($this->lastErrorMessage, 'payment-cardpay');
    }

    protected function setRawRequest($request)
    {
        $this->rawRequestToProvider = $request;
    }

    public function getException(): Exception
    {
        return $this->exception;
    }

    public function getRawRequest(): array
    {
        return $this->rawRequestToProvider;
    }

    private function setRawResponse($apiCall)
    {
        $this->rawResponseFromProvider = $apiCall;
    }

    public function getRawResponse()
    {
        return $this->rawResponseFromProvider;
    }

    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    public static function getTransactionId(array $callback)
    {
        return BaseCallback::getTransactionId($callback);
    }
}
