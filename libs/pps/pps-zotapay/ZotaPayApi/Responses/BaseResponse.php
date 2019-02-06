<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:37
 */

namespace pps\zotapay\ZotaPayApi\Responses;

use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;
use pps\zotapay\ZotaPayApi\Responses\bank\BankResponseFactory;
use pps\zotapay\ZotaPayApi\Responses\card\CardResponseFactory;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;
use yii\base\Model;

abstract class BaseResponse extends Model implements ZotaPayResponseInterface
{
    const ERROR_TYPE = 'error';
    const ERROR_VALIDATION_TYPE = 'validation-error';
    /**
     * @var ZotaPayConfig $conf
     */
    protected $conf;

    /**
     * Request factory method
     *
     * @param ZotaPayConfig $apiConfig
     * @param array $params
     * @return ZotaPayResponseInterface
     * @throws ZotaPayAPIException
     */
    public static function getResponse(ZotaPayConfig $apiConfig, array $params = []): ZotaPayResponseInterface
    {
        switch ($apiConfig->getPaymentMethod()) {
            case ZotaPayPaymentMethod::CARD:
                return CardResponseFactory::getResponse($apiConfig, $params);
            case ZotaPayPaymentMethod::BANK:
                return BankResponseFactory::getResponse($apiConfig, $params);
            default:
                throw new ZotaPayAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
        }
    }

    public function __construct(ZotaPayConfig $config, array $params = [])
    {
        parent::__construct();
        $this->conf = $config;
        $this->setAttributes($params);
        $this->validate();
    }

    public function setAttributes($values, $safeOnly = true)
    {
        $preparedValues = [];
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $preparedValues[strtr($key, ['-' => '_'])] = $value;
            }
        }
        parent::setAttributes($preparedValues, $safeOnly);
    }

    /**
     * check is exist response error
     * @return bool
     */
    public function isErrorResponse(): bool
    {
        if (property_exists(static::class, 'type')) {
            return $this->type === self::ERROR_TYPE || $this->type === self::ERROR_VALIDATION_TYPE;
        }
        return false;
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
