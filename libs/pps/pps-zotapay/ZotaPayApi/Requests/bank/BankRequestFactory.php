<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\zotapay\ZotaPayApi\Requests\bank;

use pps\zotapay\ZotaPayApi\Requests\card\CardPayoutStatusRequest;
use pps\zotapay\ZotaPayApi\Requests\ZotaPayRequestInterface;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;
use pps\zotapay\ZotaPayApi\Requests\RequestFactoryInterface;

abstract class BankRequestFactory implements RequestFactoryInterface
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
            case $apiConfig::API_MODE_ORDER_STATUS:
                throw new ZotaPayAPIException("Deposit for method '{$apiConfig->getPaymentMethod()}' not allowed!");
            case $apiConfig::API_MODE_PAYOUT:
                return new BankPayoutRequest($apiConfig, $params);
            case $apiConfig::API_MODE_PAYOUT_STATUS:
                return new CardPayoutStatusRequest($apiConfig, $params);
            default:
                throw new ZotaPayAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
        }
    }
}
