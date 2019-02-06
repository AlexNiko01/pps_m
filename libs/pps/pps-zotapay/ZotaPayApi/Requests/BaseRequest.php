<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\zotapay\ZotaPayApi\Requests;

use function GuzzleHttp\Psr7\build_query;
use GuzzleHttp\Psr7\Request;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayValidationException;
use pps\zotapay\ZotaPayApi\Requests\bank\BankRequestFactory;
use pps\zotapay\ZotaPayApi\Requests\card\CardRequestFactory;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayAPIException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

abstract class BaseRequest extends Model implements ZotaPayRequestInterface
{
    /**
     * @var ZotaPayConfig $config
     */
    protected $conf;

    /**
     * request body array
     */
    protected $requestBody;

    /**
     * BaseOrderRequest constructor.
     * @param ZotaPayConfig $config
     * @param array $params
     * @throws ZotaPayValidationException
     */
    public function __construct(ZotaPayConfig $config, array $params = [])
    {
        parent::__construct();
        $this->conf = $config;
        $this->setAttributes($params);
        if (!$this->validate()) {
            throw new ZotaPayValidationException('Wrong parameters given: ' . print_r($this->getErrors(), true));
        };
    }

    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values) && key_exists('requisites', $values)) {
            $values = ArrayHelper::merge($values, $values['requisites']);
            unset($values['requisites']);
        }
        parent::setAttributes($values, $safeOnly);
    }

    /**
     * Some fields names in request in original should have '-' instead '_' in request
     */
    protected static function getFieldsMapping(): array
    {
        return [];
    }

    /**
     * Request factory method
     *
     * @param ZotaPayConfig $apiConfig
     * @param array $params
     * @return ZotaPayRequestInterface
     * @throws ZotaPayAPIException
     */
    public static function getRequest(ZotaPayConfig $apiConfig, array $params = [])
    {
        switch ($apiConfig->getPaymentMethod()) {
            case ZotaPayPaymentMethod::CARD:
                return CardRequestFactory::getRequest($apiConfig, $params);
            case ZotaPayPaymentMethod::BANK:
                return BankRequestFactory::getRequest($apiConfig, $params);
            default:
                throw new ZotaPayAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
        }
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


    public function getMethod(): string
    {
        return $this->conf->getPaymentMethod();
    }

    /**
     * @return string
     * @throws ZotaPayAPIException
     */
    public function getRequestUrl(): string
    {
        return $this->conf->getApiUrl();
    }


    /**
     * @return array
     */
    public function getRequestBody(): array
    {
        return $this->toArray();
    }

    public function prepareResponse(string $response)
    {
        return is_string($response) ? (json_decode($response, true)) : $response;
    }

    /**
     * @return Request
     * @throws ZotaPayAPIException
     */
    public function prepareRequest(): Request
    {
        $queryString = build_query($this->getRequestBody());
        return new Request(
            'POST',
            $this->getRequestUrl(),
            ['Content-type' => 'application/x-www-form-urlencoded'],
            $queryString
        );
    }


    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $fieldsArray = parent::toArray($fields, $expand, $recursive);
        foreach ($this::getFieldsMapping() as $fieldName => $nameInRequest) {
            if (array_key_exists($fieldName, $fieldsArray)) {
                $fieldsArray[$nameInRequest] = $fieldsArray[$fieldName];
                unset($fieldsArray[$fieldName]);
            }
        }
        return $fieldsArray;
    }


    public function __toString()
    {
        return json_encode($this->getRequestBody());
    }
}
