<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 24.05.18
 * Time: 11:53
 */

namespace pps\zotapay\tests\unit;

use pps\zotapay\tests\_data\CallbackTestData;
use pps\zotapay\ZotaPayApi\Callbacks\BaseCallback;
use pps\zotapay\ZotaPayApi\Exceptions\ZotaPayValidationException;
use pps\zotapay\ZotaPayApi\ZotaPayConfig;
use pps\zotapay\ZotaPayApi\ZotaPayEndpoints;
use pps\zotapay\ZotaPayApi\ZotaPayPaymentMethod;

/**
 * @property ZotaPayConfig $config
 */
class BaseCallbackClassTest extends \Codeception\Test\Unit
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

    public function testSuccessCreateCallback()
    {
        $callback = new BaseCallback($this->config, CallbackTestData::successCallbackData());

        $this->assertEquals('123', $callback->orderid);
        $this->assertEquals('approved', $callback->status);
        $this->assertEquals('invoice-1', $callback->merchant_order);
        $this->assertEquals('100', $callback->amount);
        $this->assertEquals('5bc8ee48f9ba37c0fd1e0b052a9bc105c6df87e1', $callback->control);
        $this->assertEquals('VISA', $callback->card_type);
    }

    public function testFailCreateCallbackDueToWrongSign()
    {
        $this->expectException(ZotaPayValidationException::class);
        $request = CallbackTestData::successCallbackData();
        $request['control'] = '111xxxx1111xxxx';

        $callback = new BaseCallback($this->config, $request);
    }

    public function testGetTransactionId()
    {
        $request = CallbackTestData::successCallbackData();
        $this->assertEquals('invoice-1', BaseCallback::getTransactionId($request));
    }

    public function testToArray()
    {
        $request = CallbackTestData::successCallbackData();
        $callback = new BaseCallback($this->config, $request);
        print_r($callback->toArray());
        $this->assertCount(6, $callback->toArray());
        $this->assertArrayHasKey('status', $callback->toArray());
        $this->assertArrayHasKey('merchant_order', $callback->toArray());
        $this->assertArrayHasKey('orderid', $callback->toArray());
        $this->assertArrayHasKey('amount', $callback->toArray());
        $this->assertArrayHasKey('card_type', $callback->toArray());
        $this->assertArrayHasKey('control', $callback->toArray());

        $this->assertArrayNotHasKey('type', $callback->toArray());
        $this->assertArrayNotHasKey('last_four_digits', $callback->toArray());
    }
}
