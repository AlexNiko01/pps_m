<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 13.08.18
 * Time: 14:37
 */

namespace pps\cryptoflex\CryptoFlexApi\Responses;

use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexAPIException;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoBaseResponse;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Base response from payment system
 *
 * @property CryptoFlexResponseInterface $data
 *
 * @property string $message
 *
 * @property string $error_code
 *
 * @property string $result
 *
 */
class BaseResponse extends Model
{
    private $data;
    public $message;
    public $error_code;
    public $result;

    public function rules()
    {
        return [
            [['data', 'message', 'error_code', 'result'], 'trim']
        ];
    }

    public function extraFields()
    {
        return ['data'];
    }

    /**
     * @var CryptoFlexConfig $conf
     */
    protected $conf;

    /**
     * Request factory method
     *
     * @param CryptoFlexConfig $apiConfig
     * @param array $params
     * @return BaseResponse
     * @throws CryptoFlexAPIException
     */
    public static function getResponse(CryptoFlexConfig $apiConfig, array $params = []): BaseResponse
    {
        $i = $apiConfig->getPaymentMethod();
        if (CryptoFlexPaymentMethod::CRYPTO === $i) {
            return new self($apiConfig, $params);
        }
        throw new CryptoFlexAPIException("Payment method '{$apiConfig->getPaymentMethod()}' not allowed!");
    }

    public function __construct(CryptoFlexConfig $config, array $params = [])
    {
        parent::__construct();
        $this->conf = $config;
        $this->setAttributes($params);
    }

    /**
     * @param $responseData
     * @throws CryptoFlexAPIException
     */
    public function setData($responseData)
    {
        if (!\is_array($responseData)) {
            $responseData = [];
        }
        $this->data = CryptoBaseResponse::getResponse($this->conf, $responseData);
    }

    /**
     * @return CryptoFlexResponseInterface
     */
    public function getData(): CryptoFlexResponseInterface
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getResponseBody(): array
    {
        return $this->toArray([], ['data']);
    }


    public function __toString()
    {
        return json_encode($this->getResponseBody());
    }
}
