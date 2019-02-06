<?php

namespace pps\payment;

use api\classes\ApiError;
use Yii;
use yii\base\InvalidParamException;
use yii\base\Model;

/**
 * Class Payment
 * @package pps\payment
 */
abstract class Payment
{
    // Transaction statuses
    const STATUS_UNDEFINED = -1;     // Where transaction status is null
    const STATUS_CREATED = 0;        // Transaction was created
    const STATUS_PENDING = 1;        // Transaction in processing
    const STATUS_SUCCESS = 2;        // Success transaction
    const STATUS_TIMEOUT = 3;        // Timeout status if payment system didn't answer
    const STATUS_CANCEL = 4;         // Transaction was canceled
    const STATUS_ERROR = 5;          // Transaction error
    const STATUS_MISPAID = 6;        // The payment amount is less than the invoice amount
    const STATUS_DSPEND = 7;         // Double Spend status
    const STATUS_VOIDED = 8;         // Transaction was voided (in case of void notification)
    const STATUS_REFUNDED = 9;       // Transaction was refunded (in case of refund notification)
    const STATUS_UNCONFIRMED = 10;   // unconfirmed status
    const STATUS_NETWORK_ERROR = 11; // Network error on the side of the payment system.
    const STATUS_PENDING_ERROR = 12; // An error occurred on the payment on the side of the payment system.

    // Transaction ways
    const WAY_DEPOSIT = 'deposit';
    const WAY_WITHDRAW = 'withdraw';

    // Commissions payers
    const COMMISSION_UNKNOWN = 'unknown'; // Payment method doesn't support commission select
    const COMMISSION_NONE = 'none'; // No one pays the commission
    const COMMISSION_BUYER = 'buyer';
    const COMMISSION_MERCHANT = 'merchant';

    // Errors
    const ERROR_TRANSACTION_ID = 'transaction_id is not unique!';
    const ERROR_OCCURRED = 'An error has occurred!';
    const ERROR_COMMISSION_PAYER = 'The definition payer of commission is not supported!';
    const ERROR_NETWORK = 'Network Error!';
    const ERROR_METHOD = "Method doesn't supported";

    // Mods
    const MODE_CHANGED_AMOUNT = 'changed_amount';

    /**
     * Global payment timeout for curl option
     * @var int
     */
    public static $timeout = 7;
    /**
     * Global payment connect timeout for curl option
     * @var int
     */
    public static $connect_timeout = 5;
    /**
     * @var ILogger
     */
    protected $logger;
    protected static $methods = [];
    /** @var int */
    protected static $error_code = ApiError::PAYMENT_SYSTEM_ERROR;

    /**
     * Payment constructor.
     * @param $contractData
     */
    public function __construct(array $contractData)
    {
        if (!empty($contractData)) {
            $this->fillCredentials($contractData);
        } else {
            throw new InvalidParamException('"contract data" are empty');
        }
    }

    /**
     * @return array
     */
    public static function getMethods()
    {
        return static::$methods;
    }

    /**
     * @param $method
     * @param array $data
     * @return bool|mixed
     */
    public function callMethod($method, $data = [])
    {
        if (in_array($method, static::$methods)) {
            return call_user_func('static::' . $method . 'Method', $data);
        }

        return false;
    }

    /**
     * @param string $logTitle
     * @param string $logMessage
     * @param string $echoMessage
     * @param string $category
     */
    protected function logAndDie(string $logTitle, string $logMessage = '', string $echoMessage = '', string $category = 'logAndDie')
    {
        Yii::error([
            'title' => $logTitle,
            'message' => $logMessage,
        ], 'payment-' . $category);

        die($echoMessage);
    }

    /**
     * @param ILogger $logger
     */
    public function setLogger(ILogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get/update transaction status (only withdraw)
     * @param object $transaction
     * @param null|object $model_req
     * @return array
     */
    public function getStatus($transaction, $model_req = null)
    {
        $this->updateStatus($transaction, $model_req);

        return [
            'data' => $transaction::getNoteStructure($transaction)
        ];
    }

    /**
     * Updating not final statuses
     * @param object $transaction
     * @param null|object $model_req
     * @return bool
     */
    public function updateStatus($transaction, $model_req = null)
    {
        return true;
    }

    /**
     * @return array
     */
    public static function getApiFields(): array
    {
        return [];
    }

    /**
     * Get all statuses our payment system
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_CREATED => 'Created',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_TIMEOUT => 'Timeout',
            self::STATUS_CANCEL => 'Cancel',
            self::STATUS_ERROR => 'Error',
            self::STATUS_MISPAID => 'Mispaid',
            self::STATUS_DSPEND => 'Double spend',
            self::STATUS_VOIDED => 'Voided',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_UNCONFIRMED => 'Unconfirmed',
            self::STATUS_NETWORK_ERROR => 'Network error',
            self::STATUS_PENDING_ERROR => 'Pending error',
            self::STATUS_UNDEFINED => 'Undefined',
        ];
    }

    /**
     * @param $currency
     * @return int
     */
    public static function getCoefficient($currency)
    {
        switch ($currency) {
            case 'BTC':
            case 'TBTC':
            case 'LTC':
            case 'TLTC':
                return 1000;
            default:
                return 1;
        }
    }

    /**
     * Get all not final statuses for updating transactions using CRON
     * @return array
     */
    public static function getNotFinalStatuses(): array
    {
        return [
            self::STATUS_CREATED,
            self::STATUS_PENDING,
            self::STATUS_TIMEOUT,
            self::STATUS_UNCONFIRMED,
            self::STATUS_NETWORK_ERROR,
            self::STATUS_PENDING_ERROR,
            self::STATUS_UNDEFINED,
        ];
    }

    /**
     * Get all not final statuses for updating transactions using CRON
     * @return array
     */
    public static function getFinalStatuses(): array
    {
        return [
            self::STATUS_SUCCESS,
            self::STATUS_CANCEL,
            self::STATUS_ERROR,
            self::STATUS_MISPAID,
            self::STATUS_DSPEND,
            self::STATUS_VOIDED,
            self::STATUS_REFUNDED
        ];
    }

    /**
     * @param int|null $status
     * @return string
     */
    public static function getStatusDescription($status): string
    {
        $statuses = self::getStatuses();

        return $statuses[$status] ?? '';
    }

    /**
     * Check incoming required params
     * @param array $params
     * @param array $data
     * @return bool
     */
    protected static function checkRequiredParams(array $params, array $data): bool
    {
        foreach ($params as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fill the class with the necessary parameters
     * @param array $data
     */
    abstract public function fillCredentials(array $data);

    /**
     * Preliminary calculation of the invoice.
     * Getting required fields for invoice.
     * @param array $params
     * @return array
     */
    abstract public function preInvoice(array $params): array;

    /**
     * Invoicing for payment
     * @param array $params
     * @return array
     */
    abstract public function invoice(array $params): array;

    /**
     * Method for receiving and updating statuses
     * @param array $data
     * @return bool
     */
    abstract public function receive(array $data): bool;

    /**
     * Check if the seller has enough money.
     * Getting required fields for withdraw.
     * This method should be called before withDraw()
     * @param array $params
     * @return array
     */
    abstract public function preWithDraw(array $params);

    /**
     * Start transferring money from the merchant to the buyer
     * @param array $params
     * @return array
     */
    abstract public function withDraw(array $params);

    /**
     * Get supported currencies
     * @return array
     */
    abstract public static function getSupportedCurrencies(): array;

    /**
     * Get model for validation incoming data
     * @return Model
     */
    abstract public static function getModel(): Model;

    /**
     * Get query for search transaction after success or fail payment
     * @param array $data
     * @return array
     */
    abstract public static function getTransactionQuery(array $data): array;

    /**
     * Get transaction id.
     * Different payment systems has different keys for this value
     * @param array $data
     * @return int
     */
    abstract public static function getTransactionID(array $data);

    /**
     * Get success answer for stopping send callback data
     * @return array|string
     */
    abstract public static function getSuccessAnswer();

    /**
     * Get response format for success answer
     * @return string
     */
    abstract public static function getResponseFormat();
}