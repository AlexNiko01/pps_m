<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 16:19
 */

namespace pps\zotapay\ZotaPayApi;

use pps\payment\Payment;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;

class ZotaPayConfig
{
    const API_MODE_ORDER_STATUS = ZotaPayEndpoints::ACTION_ORDER_STATUS;
    const API_MODE_PAYOUT_STATUS = ZotaPayEndpoints::ACTION_PAYOUT_STATUS;
    const API_MODE_PAYOUT = ZotaPayEndpoints::ACTION_PAYOUT;
    const API_MODE_ORDER = ZotaPayEndpoints::ACTION_ORDER;

    // test or live
    private $mode;

    private $paymentMethod;

    // deposit or payout
    private $paymentWay;

    private $controlKey;
    private $endpointId;
    private $login;


    //контекст для создания объектов запроса и ответа
    private $apiMode;

    /**
     * ZotaPayConfig constructor.
     * @param string $paymentMethod
     * @param int $mode
     * @throws Exceptions\ZotaPayValidationException
     */
    public function __construct(string $paymentMethod, int $mode = ZotaPayEndpoints::LIVE)
    {
        PaymentValidator::allowValidate($mode, [ZotaPayEndpoints::TEST, ZotaPayEndpoints::LIVE], "mode");
        $this->setPaymentMethod($paymentMethod);
        $this->mode = $mode;
    }

    /**
     * @param string $paymentMethod
     * @throws Exceptions\ZotaPayValidationException
     */
    public function setPaymentMethod(string $paymentMethod)
    {
        PaymentValidator::allowValidate($paymentMethod, ZotaPayPaymentMethod::LIST, "Payment method");
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return string
     * @throws Exceptions\ZotaPayAPIException
     */
    public function getApiUrl()
    {
        if (isset($this->apiMode)) {
            return ZotaPayEndpoints::getEndPointUrl(
                $this->mode,
                $this->apiMode
            ) . $this->endpointId;
        }
        throw new ZotaPayAPIException('Please, setup API mode (setApiMode())');
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
     * @param $endpointId
     * @return $this
     * @throws Exceptions\ZotaPayValidationException
     */
    public function setEndpointId($endpointId)
    {
//        $walletId = (integer)$walletId;
        PaymentValidator::integerValidate($endpointId, 'Endpoint Id');
        $this->endpointId = $endpointId;
        return $this;
    }

    public function getEndpointId()
    {
        return $this->endpointId;
    }

    /**
     * @param $secretKey
     * @return $this
     * @throws Exceptions\ZotaPayValidationException
     */
    public function setControlKey($secretKey)
    {
        PaymentValidator::secretValidate($secretKey, "Control Key");
        $this->controlKey = $secretKey;
        return $this;
    }

    public function getControlKey()
    {
        return $this->controlKey;
    }

    /**
     * @param $clientLogin
     * @return $this
     * @throws Exceptions\ZotaPayValidationException
     */
    public function setClientLogin($clientLogin)
    {
        PaymentValidator::stringValidate($clientLogin, "Login", 1, 200);
        $this->login = $clientLogin;
        return $this;
    }

    public function getClientLogin()
    {
        return $this->login;
    }

    /**
     * @param string $apiMode
     * @throws Exceptions\ZotaPayValidationException
     */
    public function setApiMode(string $apiMode)
    {
        PaymentValidator::allowValidate(
            $apiMode,
            [
                ZotaPayEndpoints::ACTION_PAYOUT_STATUS,
                ZotaPayEndpoints::ACTION_PAYOUT,
                ZotaPayEndpoints::ACTION_ORDER_STATUS,
                ZotaPayEndpoints::ACTION_ORDER
            ],
            "Api mode"
        );
        $this->apiMode = $apiMode;
    }

    public function getApiMode()
    {
        return $this->apiMode;
    }

    /**
     * @param string $paymentWay
     * @throws Exceptions\ZotaPayValidationException
     */
    public function setPaymentWay(string $paymentWay)
    {
        PaymentValidator::allowValidate(
            $paymentWay,
            [
                Payment::WAY_DEPOSIT,
                Payment::WAY_WITHDRAW,
            ],
            "Payment way"
        );
        $this->paymentWay = $paymentWay;
    }

    public function getPaymentWay()
    {
        return $this->paymentWay;
    }
}
