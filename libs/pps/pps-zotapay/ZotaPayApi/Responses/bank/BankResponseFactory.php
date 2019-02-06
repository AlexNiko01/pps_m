<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\zotapay\ZotaPayApi\Responses\bank;

use pps\zotapay\ZotaPayApi\Responses\ResponseFactoryInterface;
use pps\zotapay\ZotaPayApi\Responses\ZotaPayResponseInterface;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;

abstract class BankResponseFactory implements ResponseFactoryInterface
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
            case $apiConfig::API_MODE_ORDER_STATUS:
            case $apiConfig::API_MODE_ORDER:
                throw new ZotaPayAPIException("Deposit for method '{$apiConfig->getPaymentMethod()}' not allowed!");
            case $apiConfig::API_MODE_PAYOUT:
                return new BankPayoutResponse($apiConfig, $params);
            case $apiConfig::API_MODE_PAYOUT_STATUS:
                return new BankPayoutStatusResponse($apiConfig, $params);
            default:
                throw new ZotaPayAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
                break;
        }
    }
}
