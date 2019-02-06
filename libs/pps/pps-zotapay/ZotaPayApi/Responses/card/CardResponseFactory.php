<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\zotapay\ZotaPayApi\Responses\card;

use pps\zotapay\ZotaPayApi\Responses\ResponseFactoryInterface;
use pps\zotapay\ZotaPayApi\Responses\ZotaPayResponseInterface;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;

abstract class CardResponseFactory implements ResponseFactoryInterface
{
    /**
     * @param ZotaPayConfig $apiConfig
     * @param array $params
     * @return ZotaPayResponseInterface
     * @throws ZotaPayAPIException
     */
    public static function getResponse(ZotaPayConfig $apiConfig, array $params = []): ZotaPayResponseInterface
    {
        switch ($apiConfig->getApiMode()) {
            case $apiConfig::API_MODE_ORDER:
                return new CardOrderResponse($apiConfig, $params);
            case $apiConfig::API_MODE_PAYOUT:
                return new CardPayoutResponse($apiConfig, $params);
            case $apiConfig::API_MODE_ORDER_STATUS:
                return new CardOrderStatusResponse($apiConfig, $params);
            case $apiConfig::API_MODE_PAYOUT_STATUS:
                return new CardPayoutStatusResponse($apiConfig, $params);
            default:
                throw new ZotaPayAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
                break;
        }
    }
}
