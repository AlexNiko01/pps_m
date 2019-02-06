<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 21.11.18
 * Time: 16:55
 */

namespace pps\cryptoflex\tests\unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use pps\cryptoflex\CryptoFlexApi\Callbacks\OrderCallback;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexApi;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexEndpoints;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoOrderResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoPayoutResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoPayoutStatusResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoWalletBalanceResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoWalletCreateResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\NullResponse;
use pps\cryptoflex\tests\_data\ApiTestData;
use pps\payment\Payment;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class CryptoFlexApiTest
 */
class CryptoFlexApiTest extends \Codeception\Test\Unit
{
    /**
     * @var CryptoFlexConfig $config
     */
    protected $config;
    /**
     * @var CryptoFlexApi $api
     */
    protected $api;

    /**
     * @throws CryptoFlexValidationException
     */
    protected function _before()
    {
        $this->config = new CryptoFlexConfig(
            CryptoFlexPaymentMethod::CRYPTO,
            CryptoFlexEndpoints::TEST
        );
        $this->config->setPartnerId(ApiTestData::PARTNER_ID);
        $this->config->setSecretKey(ApiTestData::SECRET_KEY);
        $this->config->setConfirmationsTrans(ApiTestData::CONFIRM_TRANSACTION);
        $this->config->setConfirmationsPayout(ApiTestData::CONFIRM_PAYOUT);
        $this->config->setConfirmationsWithdraw(ApiTestData::CONFIRM_WITHDRAW);
        $this->config->setWalletAddress(ApiTestData::WALLET_ADDRESS);
        $this->config->setFeeLevel(ApiTestData::FEE_LEVEL);
        $this->config->setDenomination(ApiTestData::DENOMINATION);

        $this->config->setApiMode(CryptoFlexConfig::API_MODE_ORDER);
        $this->config->setPaymentWay(Payment::WAY_DEPOSIT);

        $this->api = new CryptoFlexApi($this->config);
    }

    /**
     *
     */
    public function testGetCallbackSuccess()
    {
        $requestData = ApiTestData::getData('callback', 'request');
        $callback = $this->api->getCallback($requestData);
        $this->assertInstanceOf(OrderCallback::class, $callback);
        foreach ($requestData as $key => $val) {
            $this->assertAttributeEquals($val, $key, $callback);
        }
    }

    /**
     *
     */
    public function testWrongSignCallbackFail()
    {
        $requestData = ApiTestData::getData('callback', 'request');
        $requestData['sign'] = 'b2aa28885b56b74f9eff8a5f934731ecec679b713b3b225c98db5c13d4924114';
        $callback = $this->api->getCallback($requestData);
        $this->assertFalse($callback);
        $this->assertNotEmpty($this->api->lastErrorMessage);
        $this->assertEquals(0, $this->api->getErrorCode());

        $this->assertInstanceOf(CryptoFlexValidationException::class, $this->api->getException());
    }

    /**
     * @throws CryptoFlexValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testGetAddress()
    {
        $this->prepareClient(ApiTestData::getData('order', 'response', true));
        $address = $this->api->getAddress(ApiTestData::getData('order', 'request'), CryptoFlexPaymentMethod::CRYPTO);
        $this->assertInstanceOf(CryptoOrderResponse::class, $address);
        $responseData = ApiTestData::getData('order', 'response');
        foreach ($responseData['data'] as $key => $val) {
            $this->assertAttributeEquals($val, $key, $address);
        }
    }

    /**
     * @throws CryptoFlexValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testErrorGetAddress()
    {
        $this->prepareClient(ApiTestData::getData('order', 'error', true));
        $address = $this->api->getAddress(ApiTestData::getData('order', 'request'), CryptoFlexPaymentMethod::CRYPTO);
        $this->assertInstanceOf(NullResponse::class, $address);

        $this->assertEquals(100, $this->api->getErrorCode());
        $this->assertNotEmpty($this->api->lastErrorMessage);
    }

    /**
     * @throws CryptoFlexValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testCreateWallet()
    {
        $this->prepareClient(ApiTestData::getData('wallet_create', 'response', true));
        $wallet = $this->api->createWallet('BTC');
        $this->assertInstanceOf(CryptoWalletCreateResponse::class, $wallet);
        $responseData = ApiTestData::getData('wallet_create', 'response');
        $this->assertAttributeEquals($responseData['data']['wallet_address'], 'wallet_address', $wallet);

        $this->assertArrayHasKey('partner_id', $this->api->getRawRequest());
        $this->assertArrayHasKey('crypto_currency', $this->api->getRawRequest());

        $this->assertStringEndsWith('create_wallet', $this->api->getRequestUrl());

        $this->assertArrayHasKey('data', $this->api->getRawResponse());
    }

    /**
     * @throws CryptoFlexValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testGetWalletBalance()
    {
        $this->prepareClient(ApiTestData::getData('wallet_balance', 'response', true));
        $balance = $this->api->getWalletBalance('BTC', ApiTestData::WALLET_ADDRESS);
        $this->assertInstanceOf(CryptoWalletBalanceResponse::class, $balance);
        $responseData = ApiTestData::getData('wallet_balance', 'response');
        $this->assertAttributeEquals($responseData['data']['wallet_address'], 'wallet_address', $balance);
        $this->assertAttributeEquals($responseData['data']['balance'], 'balance', $balance);
        $this->assertAttributeEquals($responseData['data']['crypto_currency'], 'crypto_currency', $balance);
    }

    /**
     * @throws CryptoFlexValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testCreateWithdrawal()
    {
        $this->prepareClient(ApiTestData::getData('withdraw', 'response', true));
        $withdrawal = $this->api->createWithdrawal(
            ApiTestData::getData('withdraw', 'request'),
            CryptoFlexPaymentMethod::CRYPTO
        );
        $this->assertInstanceOf(CryptoPayoutResponse::class, $withdrawal);
        $responseData = ApiTestData::getData('withdraw', 'response');
        foreach ($responseData['data'] as $key => $val) {
            if ($key === 'fee_value') {
                $val = (float)$val;
            }
            $this->assertAttributeEquals($val, $key, $withdrawal);
        }
    }

    /**
     *
     */
    public function testConstruct()
    {
        $this->assertInstanceOf(CryptoFlexApi::class, $this->api);
    }

    /**
     * @throws CryptoFlexValidationException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testGetWithdrawalStatus()
    {
        $this->prepareClient(ApiTestData::getData('withdraw_status', 'response', true));
        $withdrawalStatus = $this->api->getWithdrawalStatus(
            'BTC',
            'cryptoflex_wd_1',
            ApiTestData::WALLET_ADDRESS
        );
        $this->assertInstanceOf(CryptoPayoutStatusResponse::class, $withdrawalStatus);
        $responseData = ApiTestData::getData('withdraw', 'response');
        foreach ($responseData['data'] as $key => $val) {
            if ($key === 'fee_value') {
                $val = (float)$val;
            }
            $this->assertAttributeEquals($val, $key, $withdrawalStatus);
        }
    }

    /**
     *
     */
    public function testCheckRequestFieldsSuccess()
    {
        $data = ApiTestData::getData('withdraw', 'request');
        $check = $this->api->checkRequestFields(
            CryptoFlexPaymentMethod::CRYPTO,
            CryptoFlexConfig::API_MODE_PAYOUT,
            $data
        );
        $this->assertTrue($check);
    }

    /**
     *
     */
    public function testCheckRequestFieldsFail()
    {
        $data = ApiTestData::getData('withdraw', 'request');
        unset($data['requisites']);
        $check = $this->api->checkRequestFields(
            CryptoFlexPaymentMethod::CRYPTO,
            CryptoFlexConfig::API_MODE_PAYOUT,
            $data
        );
        $this->assertFalse($check);
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
