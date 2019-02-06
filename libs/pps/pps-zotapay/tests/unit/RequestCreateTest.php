<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 24.05.18
 * Time: 11:53
 */

namespace pps\zotapay\tests\unit;

use pps\payment\Payment;
use pps\zotapay\ZotaPayApi\Requests\bank\BankPayoutRequest;
use pps\zotapay\ZotaPayApi\Requests\BaseRequest;
use pps\zotapay\ZotaPayApi\Requests\card\CardOrderRequest;
use pps\zotapay\ZotaPayApi\Requests\card\CardOrderStatusRequest;
use pps\zotapay\ZotaPayApi\Requests\card\CardPayoutStatusRequest;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\ZotaPayEndpoints;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;

/**
 * @property ZotaPayConfig $config
 */
class RequestCreateTest extends \Codeception\Test\Unit
{
    protected $config;

    protected function setUp()
    {
        $this->config = new ZotaPayConfig(
            ZotaPayPaymentMethod::CARD,
            ZotaPayEndpoints::TEST
        );
        $this->config->setClientLogin('login');
        $this->config->setControlKey('AF4B5DE6-3468-424C-A922-C1DAD7CB4509');
        $this->config->setEndpointId('13237');
    }

    public function testSuccessCreateOrder()
    {
        $this->config->setPaymentMethod(ZotaPayPaymentMethod::CARD);
        $this->config->setApiMode(ZotaPayEndpoints::ACTION_ORDER);
        $this->config->setPaymentWay(Payment::WAY_DEPOSIT);

        $data = [
            'amount' => 1,
            'currency' => 'EUR',
            'redirect_url' => 'http://redirect.test',
            'client_orderid' => 'test_id',
            'order_desc' => 'test order',
            'server_callback_url' => 'http://callback.test',
            'requisites' => [
                'first_name' => 'John',
                'last_name' => 'Dow',
                'address1' => 'Ryb 3',
                'city' => 'Nyw York',
                'zip_code' => '11111',
                'country' => 'US',
                'phone' => '+890458968785',
                'email' => 'test@gmail.com',
                'ipaddress' => '127.0.0.1',
            ]
        ];

        /**
         * @var CardOrderRequest $request
        */
        $request = BaseRequest::getRequest($this->config, $data);

        $this->assertAttributeEquals(1, 'amount', $request);
        $this->assertAttributeEquals('EUR', 'currency', $request);
        $this->assertAttributeEquals('http://redirect.test', 'redirect_url', $request);
        $this->assertAttributeEquals('test_id', 'client_orderid', $request);
        $this->assertAttributeEquals('test order', 'order_desc', $request);
        $this->assertAttributeEquals('http://callback.test', 'server_callback_url', $request);
        $this->assertAttributeEquals('John', 'first_name', $request);
        $this->assertAttributeEquals('Dow', 'last_name', $request);
        $this->assertAttributeEquals('Ryb 3', 'address1', $request);
        $this->assertAttributeEquals('Nyw York', 'city', $request);
        $this->assertAttributeEquals('11111', 'zip_code', $request);
        $this->assertAttributeEquals('US', 'country', $request);
        $this->assertAttributeEquals('+890458968785', 'phone', $request);
        $this->assertAttributeEquals('test@gmail.com', 'email', $request);
        $this->assertAttributeEquals('127.0.0.1', 'ipaddress', $request);

        $this->assertAttributeNotEmpty('control', $request);

        $this->assertEquals('https://sandbox.zotapay.com/paynet/api/v2/sale-form/13237', $request->getRequestUrl());
    }

    public function testSuccessCreateOrderStatus()
    {
        $this->config->setPaymentMethod(ZotaPayPaymentMethod::CARD);
        $this->config->setApiMode(ZotaPayEndpoints::ACTION_ORDER_STATUS);
        $this->config->setPaymentWay(Payment::WAY_DEPOSIT);

        $data = [
            'client_orderid' => 'safafag23542345ttg',
            'orderid' => '1111111111111111111111111111',
            'by_request_sn' => 'test_test',
        ];

        /**
         * @var CardOrderStatusRequest $request
        */
        $request = BaseRequest::getRequest($this->config, $data);

        $this->assertAttributeEquals('safafag23542345ttg', 'client_orderid', $request);
        $this->assertAttributeEquals('1111111111111111111111111111', 'orderid', $request);
        $this->assertAttributeEquals('test_test', 'by_request_sn', $request);
        $this->assertAttributeEquals('login', 'login', $request);

        $this->assertAttributeNotEmpty('control', $request);

        $this->assertEquals('https://sandbox.zotapay.com/paynet/api/v2/status/13237', $request->getRequestUrl());
    }

    public function testSuccessCreatePayoutStatus()
    {
        $this->config->setPaymentMethod(ZotaPayPaymentMethod::CARD);
        $this->config->setApiMode(ZotaPayEndpoints::ACTION_PAYOUT_STATUS);
        $this->config->setPaymentWay(Payment::WAY_WITHDRAW);

        $data = [
            'client_orderid' => 'safafag23542345ttg',
            'orderid' => '1111111111111111111111111111',
            'by_request_sn' => 'test_test',
        ];

        /**
         * @var CardOrderStatusRequest $request
         */
        $request = BaseRequest::getRequest($this->config, $data);

        $this->assertInstanceOf(CardPayoutStatusRequest::class, $request);

        $this->assertAttributeEquals('safafag23542345ttg', 'client_orderid', $request);
        $this->assertAttributeEquals('1111111111111111111111111111', 'orderid', $request);
        $this->assertAttributeEquals('test_test', 'by_request_sn', $request);
        $this->assertAttributeEquals('login', 'login', $request);

        $this->assertAttributeNotEmpty('control', $request);

        $this->assertEquals('https://sandbox.zotapay.com/paynet/api/v2/status/13237', $request->getRequestUrl());
    }

    public function testSuccessCreatePayout()
    {
        $this->config->setPaymentMethod(ZotaPayPaymentMethod::BANK);
        $this->config->setApiMode(ZotaPayEndpoints::ACTION_PAYOUT);
        $this->config->setPaymentWay(Payment::WAY_WITHDRAW);

        $data = [
            'amount' => 1,
            'currency' => 'EUR',
            //'redirect_url' => 'http://redirect.test',
            'client_orderid' => 'test_id',
            'order_desc' => 'test order',
            'server_callback_url' => 'http://callback.test',
            'requisites' => [
                'first_name' => 'John',
                'last_name' => 'Dow',
                'bank_branch' => 'Test_Branch',
                'bank_name' => 'Pireus Bank',
                'account_number' => '1111111111111111',
                'routing_number' => '123456789',
            ]
        ];

        /**
         * @var CardOrderStatusRequest $request
         */
        $request = BaseRequest::getRequest($this->config, $data);

        $this->assertInstanceOf(BankPayoutRequest::class, $request);

        $this->assertAttributeEquals(1, 'amount', $request);
        $this->assertAttributeEquals('EUR', 'currency', $request);
        $this->assertAttributeEquals('test_id', 'client_orderid', $request);
        $this->assertAttributeEquals('test order', 'order_desc', $request);
        $this->assertAttributeEquals('http://callback.test', 'server_callback_url', $request);
        $this->assertAttributeEquals('John', 'first_name', $request);
        $this->assertAttributeEquals('Dow', 'last_name', $request);
        $this->assertAttributeEquals('Test_Branch', 'bank_branch', $request);
        $this->assertAttributeEquals('Pireus Bank', 'bank_name', $request);
        $this->assertAttributeEquals('1111111111111111', 'account_number', $request);
        $this->assertAttributeEquals('123456789', 'routing_number', $request);

        $this->assertEquals('https://sandbox.zotapay.com/paynet/api/v2/payout/13237', $request->getRequestUrl());

        echo $request;
    }
}
