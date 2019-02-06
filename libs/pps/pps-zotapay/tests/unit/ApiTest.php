<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 24.05.18
 * Time: 11:53
 */

namespace pps\zotapay\tests\unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use pps\zotapay\tests\_data\ApiTestData;
use pps\zotapay\ZotaPayApi\Responses\bank\BankPayoutResponse;
use pps\zotapay\ZotaPayApi\Responses\bank\BankPayoutStatusResponse;
use pps\zotapay\ZotaPayApi\Responses\card\CardOrderResponse;
use pps\zotapay\ZotaPayApi\ZotaPayApi;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\ZotaPayEndpoints;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * @property ZotaPayConfig $config
 */
class ApiTest extends \Codeception\Test\Unit
{
    protected $config;

    /**
     * @var ZotaPayApi $api
     */
    protected $api;

    /**
     * @throws \pps\zotapay\ZotaPayApi\Exceptions\ZotaPayValidationException
     */
    protected function setUp()
    {
        $this->config = new ZotaPayConfig(
            ZotaPayPaymentMethod::CARD,
            ZotaPayEndpoints::TEST
        );
        $this->config->setClientLogin('login');
        $this->config->setControlKey('AF4B5DE6-3468-424C-A922-C1DAD7CB4509');
        $this->config->setEndpointId('13237');

        $this->api = new ZotaPayApi($this->config);
    }

    public function testOrderCreate()
    {
        $this->prepareClient(ApiTestData::getData('order', 'response', true));
        $order = $this->api->createOrder(ApiTestData::getData('order', 'request'), ZotaPayPaymentMethod::CARD);
        $this->assertInstanceOf(CardOrderResponse::class, $order);
        $responseData = ApiTestData::getData('order', 'response');
        foreach ($responseData as $key => $val) {
            $this->assertAttributeEquals($val, str_replace('-', '_', $key), $order);
        }
    }

    public function testErrorOrderCreate()
    {
        $this->prepareClient(ApiTestData::getData('order', 'errorResponse', true));
        $order = $this->api->createOrder(ApiTestData::getData('order', 'request'), ZotaPayPaymentMethod::CARD);
        $this->assertInstanceOf(CardOrderResponse::class, $order);
        $responseData = ApiTestData::getData('order', 'errorResponse');
        foreach ($responseData as $key => $val) {
            $this->assertAttributeEquals($val, str_replace('-', '_', $key), $order);
        }
    }

    public function testPayoutCreate()
    {
        $this->prepareClient(ApiTestData::getData('withdraw', 'response', true));
        $order = $this->api->createWithdrawal(ApiTestData::getData('withdraw', 'request'), ZotaPayPaymentMethod::BANK);
        $this->assertInstanceOf(BankPayoutResponse::class, $order);
        $responseData = ApiTestData::getData('withdraw', 'response');
        foreach ($responseData as $key => $val) {
            $this->assertAttributeEquals($val, str_replace('-', '_', $key), $order);
        }
    }

    public function testErrorPayoutCreate()
    {
        $this->prepareClient(ApiTestData::getData('withdraw', 'errorResponse', true));
        $order = $this->api->createWithdrawal(ApiTestData::getData('withdraw', 'request'), ZotaPayPaymentMethod::BANK);
        $this->assertInstanceOf(BankPayoutResponse::class, $order);
        $responseData = ApiTestData::getData('withdraw', 'errorResponse');
        foreach ($responseData as $key => $val) {
            $this->assertAttributeEquals($val, str_replace('-', '_', $key), $order);
        }
    }

    public function testPayoutStatusCreate()
    {
        $this->config->setPaymentMethod(ZotaPayPaymentMethod::BANK);
        $data = ApiTestData::getData('withdraw_status', 'request');
        $this->prepareClient(ApiTestData::getData('withdraw_status', 'response', true));
        $order = $this->api->getTransactionStatus(
            'withdraw',
            $data['orderid'],
            $data['client_orderid'],
            $data['by_request_sn']
        );
        $this->assertInstanceOf(BankPayoutStatusResponse::class, $order);
        $responseData = ApiTestData::getData('withdraw_status', 'response');
        foreach ($responseData as $key => $val) {
            $this->assertAttributeEquals($val, str_replace('-', '_', $key), $order);
        }
    }
    /**
     * @param string $data
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function prepareClient(string $data)
    {
        $response = Yii::$container->get(Response::class, [200, ['Content-Type' => 'application/json'], $data]);
        /**
         * @var MockHandler $mock
         */
        $mock = Yii::$container->get(MockHandler::class, [[$response]]);
        $handler = HandlerStack::create($mock);
        Yii::$container->setSingleton(
            Client::class,
            function ($container, $params, $config) use ($handler) {
                $config = ArrayHelper::merge($config, ['handler' => $handler]);
                return new Client($config);
            }
        );
    }
}
