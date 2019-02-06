<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\zotapay\ZotaPayApi\Requests\card;

use pps\zotapay\ZotaPayApi\Requests\ZotaPayRequestInterface;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;
use pps\zotapay\ZotaPayApi\Requests\RequestFactoryInterface;

abstract class CardRequestFactory implements RequestFactoryInterface
{
    /**
     * @param ZotaPayConfig $apiConfig
     * @param array $params
     * @return ZotaPayRequestInterface
     * @throws ZotaPayAPIException
     */
    public static function getRequest(ZotaPayConfig $apiConfig, array $params = []): ZotaPayRequestInterface
    {
        switch ($apiConfig->getApiMode()) {
            case $apiConfig::API_MODE_ORDER:
                return new CardOrderRequest($apiConfig, $params);
                break;
            case $apiConfig::API_MODE_ORDER_STATUS:
                return new CardOrderStatusRequest($apiConfig, $params);
                break;
            case $apiConfig::API_MODE_PAYOUT:
                throw new ZotaPayAPIException("Withdraw for method '{$apiConfig->getPaymentMethod()}' not allowed!");
            case $apiConfig::API_MODE_PAYOUT_STATUS:
                return new CardPayoutStatusRequest($apiConfig, $params);
                break;
            default:
                throw new ZotaPayAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
                break;
        }
    }
}
