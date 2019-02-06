<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 16:19
 */

namespace pps\cryptoflex\CryptoFlexApi;

use pps\payment\Payment;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;

class CryptoFlexConfig
{
    const API_MODE_PAYOUT_STATUS = CryptoFlexEndpoints::ACTION_PAYOUT_STATUS;
    const API_MODE_PAYOUT = CryptoFlexEndpoints::ACTION_PAYOUT;
    const API_MODE_ORDER = CryptoFlexEndpoints::ACTION_ORDER;
    const API_MODE_WALLET_BALANCE = CryptoFlexEndpoints::ACTION_WALLET_BALANCE;
    const API_MODE_WALLET_CREATE = CryptoFlexEndpoints::ACTION_WALLET_CREATE;

    // test or live
    private $mode;

    private $paymentMethod;

    // deposit or payout
    private $paymentWay;

    private $partnerId;
    private $secretKey;
    private $feeLevel;
    private $confirmationsPayout;
    private $confirmationsTrans;
    private $confirmationsWithdraw;
    private $walletAddress;

    private $denomination;


    //контекст для создания объектов запроса и ответа
    private $apiMode;

    public static $feeLevels = ['high', 'medium', 'low'];

    /**
     * CryptoFlexConfig constructor.
     * @param string $paymentMethod
     * @param int $mode
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function __construct(string $paymentMethod, int $mode = CryptoFlexEndpoints::LIVE)
    {
        PaymentValidator::allowValidate($mode, [CryptoFlexEndpoints::TEST, CryptoFlexEndpoints::LIVE], 'mode');
        $this->setPaymentMethod($paymentMethod);
        $this->mode = $mode;
    }

    /**
     * @param string $paymentMethod
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setPaymentMethod(string $paymentMethod)
    {
        PaymentValidator::allowValidate($paymentMethod, CryptoFlexPaymentMethod::LIST, 'Payment method');
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return string
     * @throws Exceptions\CryptoFlexAPIException
     */
    public function getApiUrl(): string
    {
        if ($this->apiMode !== null) {
            return CryptoFlexEndpoints::getEndPointUrl(
                $this->mode,
                $this->apiMode
            );
        }
        throw new CryptoFlexAPIException('Please, setup API mode (setApiMode())');
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @param $partnerId
     * @return $this
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setPartnerId($partnerId): self
    {
//        $walletId = (integer)$walletId;
        PaymentValidator::stringValidate($partnerId, 'Partner Id');
        $this->partnerId = $partnerId;
        return $this;
    }

    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @param $secretKey
     * @return $this
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setSecretKey($secretKey): self
    {
        PaymentValidator::secretValidate($secretKey, 'Secret Key');
        $this->secretKey = $secretKey;
        return $this;
    }

    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @param $feeLevel
     * @return $this
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setFeeLevel($feeLevel): self
    {
        PaymentValidator::allowValidate($feeLevel, self::$feeLevels, 'Fee level');
        $this->feeLevel = $feeLevel;
        return $this;
    }

    public function getFeeLevel()
    {
        return $this->feeLevel;
    }

    /**
     * @param $confirmationsPayout
     * @return $this
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setConfirmationsPayout($confirmationsPayout): self
    {
        PaymentValidator::integerValidate($confirmationsPayout, 'Number of payout confirmations', true);
        $this->confirmationsPayout = $confirmationsPayout;
        return $this;
    }

    public function getConfirmationsPayout()
    {
        return $this->confirmationsPayout;
    }

    /**
     * @param $confirmationsTrans
     * @return $this
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setConfirmationsTrans($confirmationsTrans): self
    {
        PaymentValidator::integerValidate($confirmationsTrans, 'Number of payout confirmations transactions', true);
        $this->confirmationsTrans = $confirmationsTrans;
        return $this;
    }

    public function getConfirmationsTrans()
    {
        return $this->confirmationsTrans;
    }

    /**
     * @param $confirmationsWithdraw
     * @return $this
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setConfirmationsWithdraw($confirmationsWithdraw): self
    {
        PaymentValidator::integerValidate($confirmationsWithdraw, 'Number of Withdraw confirmations', true);
        $this->confirmationsWithdraw = $confirmationsWithdraw;
        return $this;
    }

    public function getConfirmationsWithdraw()
    {
        return $this->confirmationsWithdraw;
    }

    /**
     * @param $walletAddress
     * @return $this
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setWalletAddress($walletAddress): self
    {
        if ($walletAddress !== '') {
            PaymentValidator::stringValidate($walletAddress, 'Wallet Address', true);
            $this->walletAddress = $walletAddress;
        }
        return $this;
    }

    public function getWalletAddress()
    {
        return $this->walletAddress;
    }

    /**
     * @param string $apiMode
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setApiMode(string $apiMode)
    {
        PaymentValidator::allowValidate(
            $apiMode,
            [
                CryptoFlexEndpoints::ACTION_PAYOUT_STATUS,
                CryptoFlexEndpoints::ACTION_PAYOUT,
                CryptoFlexEndpoints::ACTION_ORDER,
                CryptoFlexEndpoints::ACTION_WALLET_BALANCE,
                CryptoFlexEndpoints::ACTION_WALLET_CREATE,
            ],
            'Api mode'
        );
        $this->apiMode = $apiMode;
    }

    public function getApiMode()
    {
        return $this->apiMode;
    }

    /**
     * @param string $paymentWay
     * @throws Exceptions\CryptoFlexValidationException
     */
    public function setPaymentWay(string $paymentWay)
    {
        PaymentValidator::allowValidate(
            $paymentWay,
            [
                Payment::WAY_DEPOSIT,
                Payment::WAY_WITHDRAW,
            ],
            'Payment way'
        );
        $this->paymentWay = $paymentWay;
    }

    public function getPaymentWay()
    {
        return $this->paymentWay;
    }

    /**
     * @return float
     */
    public function getDenomination(): float
    {
        return (float) $this->denomination;
    }

    /**
     * @param mixed $denomination
     */
    public function setDenomination($denomination)
    {
        $this->denomination = $denomination;
    }
}
