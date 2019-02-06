<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests\crypto;

use pps\cryptoflex\CryptoFlexApi\Requests\CryptoFlexRequestInterface;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;
use pps\cryptoflex\CryptoFlexApi\Requests\RequestFactoryInterface;

abstract class CryptoRequestFactory implements RequestFactoryInterface
{
    /**
     * @param CryptoFlexConfig $apiConfig
     * @param array $params
     * @return CryptoFlexRequestInterface
     * @throws CryptoFlexAPIException
     */
    public static function getRequest(CryptoFlexConfig $apiConfig, array $params = []): CryptoFlexRequestInterface
    {
        switch ($apiConfig->getApiMode()) {
            case $apiConfig::API_MODE_ORDER:
                return new CryptoOrderRequest($apiConfig, $params);
                break;
            case $apiConfig::API_MODE_PAYOUT:
                return new CryptoPayoutRequest($apiConfig, $params);
                break;
            case $apiConfig::API_MODE_WALLET_BALANCE:
                return new CryptoWalletBalanceRequest($apiConfig, $params);
                break;
            case $apiConfig::API_MODE_PAYOUT_STATUS:
                return new CryptoPayoutStatusRequest($apiConfig, $params);
                break;
            case $apiConfig::API_MODE_WALLET_CREATE:
                return new CryptoWalletCreateRequest($apiConfig, $params);
                break;
            default:
                throw new CryptoFlexAPIException("Payment mode '{$apiConfig->getApiMode()}' not allowed!");
                break;
        }
    }
}
