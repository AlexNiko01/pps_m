<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 17.07.18
 * Time: 9:37
 */

namespace pps\cryptoflex\CryptoFlexApi\Requests;

use function GuzzleHttp\Psr7\build_query;
use GuzzleHttp\Psr7\Request;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;
use pps\cryptoflex\CryptoFlexApi\Requests\crypto\CryptoRequestFactory;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * время сервера на момент отправки запроса Unix time
 * @property string $timestamp
 *
 * подпись для валидации выплаты hash256
 * @property string $sign
 */
abstract class BaseRequest extends Model implements CryptoFlexRequestInterface
{
    /**
     * @var CryptoFlexConfig $config
     */
    protected $conf;

    /**
     * request body array
     */
    protected $requestBody;

    public $sign;
    public $timestamp;

    /**
     * BaseOrderRequest constructor.
     * @param CryptoFlexConfig $config
     * @param array $params
     * @throws CryptoFlexValidationException
     */
    public function __construct(CryptoFlexConfig $config, array $params = [])
    {
        parent::__construct();
        $this->conf = $config;
        $this->setAttributes($params);
        if (!$this->validate()) {
            throw new CryptoFlexValidationException('Wrong parameters given: ' . print_r($this->getErrors(), true));
        }
    }


    public function rules(): array
    {
        return [
            [['timestamp', 'sign'], 'required'],
        ];
    }

    public function setAttributes($values, $safeOnly = true)
    {
        if (\is_array($values) && array_key_exists('requisites', $values)) {
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
     * @param CryptoFlexConfig $apiConfig
     * @param array $params
     * @return CryptoFlexRequestInterface
     * @throws CryptoFlexAPIException
     */
    public static function getRequest(CryptoFlexConfig $apiConfig, array $params = [])
    {
        /** @noinspection DegradedSwitchInspection */
        switch ($apiConfig->getPaymentMethod()) {
            case CryptoFlexPaymentMethod::CRYPTO:
                return CryptoRequestFactory::getRequest($apiConfig, $params);
                break;
            default:
                throw new CryptoFlexAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
                break;
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
     * @throws CryptoFlexAPIException
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
        return \is_string($response) ? json_decode($response, true) : $response;
    }

    /**
     * @return Request
     * @throws CryptoFlexAPIException
     */
    public function prepareRequest(): Request
    {
        $queryString = json_encode($this->getRequestBody(), true);
        return new Request(
            'POST',
            $this->getRequestUrl(),
            ['Content-Type' => 'application/json'],
            $queryString
        );
    }


    /**
     * @param array $fields
     * @param array $expand
     * @param bool $recursive
     * @return array
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
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

    /**
     * @return bool
     */
    public function beforeValidate(): bool
    {
        $this->timestamp = time();
        $this->sign = $this->generateSign();
        return parent::beforeValidate();
    }

    public function __toString()
    {
        return json_encode($this->getRequestBody());
    }

    /**
     * @return string
     */
    protected function generateSign(): string
    {
        $fieldsToSign = $this->mandatoryFields;
        sort($fieldsToSign, SORT_STRING);
        $stringToSign = '';
        foreach ($fieldsToSign as $field) {
            $stringToSign .= $stringToSign === ''
                ? ArrayHelper::getValue($this, $field, '')
                : ':' . ArrayHelper::getValue($this, $field, '');
        }
        $stringToSign .= $this->conf->getSecretKey();
        $hash = hash('sha256', $stringToSign);

        if (YII_ENV === 'dev') {
            \Yii::info('String to sign: ' . $stringToSign, 'payment-cryptoflex-info');
            \Yii::info('Sign: ' . $hash, 'payment-cryptoflex-info');
        }

        return $hash;
    }
}
