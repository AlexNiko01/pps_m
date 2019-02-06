<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\cryptoflex\CryptoFlexApi\Responses\crypto;

use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;
use pps\cryptoflex\CryptoFlexApi\Responses\ResponseFactoryInterface;
use pps\cryptoflex\CryptoFlexApi\Responses\CryptoFlexResponseInterface;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;
use yii\base\Model;

abstract class CryptoBaseResponse extends Model implements ResponseFactoryInterface, CryptoFlexResponseInterface
{
    /**
     * @param CryptoFlexConfig $apiConfig
     * @param array $params
     * @return CryptoFlexResponseInterface
     * @throws CryptoFlexAPIException
     */
    public static function getResponse(CryptoFlexConfig $apiConfig, array $params = []): CryptoFlexResponseInterface
    {
        if ($params === []) {
            return new NullResponse($apiConfig, $params);
        }
        switch ($apiConfig->getApiMode()) {
            case $apiConfig::API_MODE_ORDER:
                return new CryptoOrderResponse($apiConfig, $params);
            case $apiConfig::API_MODE_PAYOUT:
                return new CryptoPayoutResponse($apiConfig, $params);
            case $apiConfig::API_MODE_PAYOUT_STATUS:
                return new CryptoPayoutStatusResponse($apiConfig, $params);
            case $apiConfig::API_MODE_WALLET_BALANCE:
                return new CryptoWalletBalanceResponse($apiConfig, $params);
            case $apiConfig::API_MODE_WALLET_CREATE:
                return new CryptoWalletCreateResponse($apiConfig, $params);
            default:
                throw new CryptoFlexAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
                break;
        }
    }

    /**
     * @var CryptoFlexConfig $conf
     */
    protected $conf;

    public function __construct(CryptoFlexConfig $config, array $params = [])
    {
        parent::__construct();
        $this->conf = $config;
        $this->setAttributes($params);
        if (!$this->validate()) {
            throw new CryptoFlexValidationException('Wrong parameters given: ' . print_r($this->getErrors(), true));
        }
    }

    /**
     * @return array
     */
    public function getResponseBody(): array
    {
        return $this->toArray();
    }

    /**
     * Only not empty fields should passed to toArray() method
     */
    public function fields()
    {
        $fields = [];
        foreach ($this->attributes() as $propertyName) {
            if (!empty($this->$propertyName)) {
                $fields[] = $propertyName;
            }
        }
        return array_combine($fields, $fields);
    }

    public function __toString()
    {
        return json_encode($this->getResponseBody());
    }
}
