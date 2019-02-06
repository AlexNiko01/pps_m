<?php

namespace pps\cryptoflex\tests\unit;

use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\Currencies\crypto\CurrenciesList;
use pps\cryptoflex\CryptoFlexApi\Currencies\CryptoFlexCurrency;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexItemNotExistException;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;
use pps\payment\Payment;

/**
 * @property CurrenciesList $curencies
 */
class CurrencyTest extends \Codeception\Test\Unit
{
    protected $curencies;

    protected function setUp()
    {
        $this->curencies = CryptoFlexCurrency::getCurrencyObject(CryptoFlexPaymentMethod::CRYPTO);
    }

    public function testSuccessIsSupportCurrencyMethod()
    {
        $this->assertEmpty($this->curencies->isSupportedCurrency('BTC'));
    }

    public function testFailIsSupportCurrencyMethodCurrencyNotExist()
    {
        $this->expectException(CryptoFlexItemNotExistException::class);
        $this->curencies->isSupportedCurrency('PLN');
    }

    public function testFailIsSupportCurrencyMethodCurrencyNotValid()
    {
        $this->expectException(CryptoFlexValidationException::class);
        $this->curencies->isSupportedCurrency('RU');
    }

    public function testSuccessIsSupportWayMethod()
    {
        $this->assertEmpty($this->curencies->isSupportedWay(Payment::WAY_WITHDRAW));
    }

    public function testFailIsSupportWayMethod()
    {
        $this->expectException(CryptoFlexItemNotExistException::class);
        $this->curencies->isSupportedWay(Payment::WAY_DEPOSIT);
    }

    public function testSuccessGetPaymentMethod()
    {
        $this->assertEquals(CryptoFlexPaymentMethod::CRYPTO, $this->curencies->getPaymentMethod());
    }

    public function testSuccessGetFieldsMethod()
    {
        $fields = $this->curencies->getFields('XBT', Payment::WAY_DEPOSIT);
        $this->assertEquals($fields, []);
        $fields = $this->curencies->getFields('XBT', Payment::WAY_WITHDRAW);
        $this->assertArrayHasKey('withdraw_address', $fields);
    }

    public function testSuccessGetApiFieldsMethod()
    {
        $fields = $this->curencies->getApiFields();
        $this->assertArrayHasKey('BTC', $fields);
        self::assertArrayHasKey(CryptoFlexPaymentMethod::CRYPTO, $fields['BTC']);
        self::assertArrayHasKey(Payment::WAY_DEPOSIT, $fields['BTC'][CryptoFlexPaymentMethod::CRYPTO]);
        self::assertArrayHasKey(Payment::WAY_WITHDRAW, $fields['BTC'][CryptoFlexPaymentMethod::CRYPTO]);
    }

    public function testSuccessCurrenciesListMethod()
    {
        $list = $this->curencies->getCurrenciesList();
        $this->assertContains('BTC', $list);
        $this->assertNotContains('USD', $list);
    }

    public function testSuccessGetSupportedCurrenciesMethod()
    {
        $currencies = $this->curencies->getSupportedCurrencies();
        self::assertArrayHasKey('BTC', $currencies);
        self::assertArrayHasKey(CryptoFlexPaymentMethod::CRYPTO, $currencies['BTC']);
        self::assertArrayHasKey(Payment::WAY_DEPOSIT, $currencies['BTC'][CryptoFlexPaymentMethod::CRYPTO]);
        self::assertArrayHasKey(Payment::WAY_WITHDRAW, $currencies['BTC'][CryptoFlexPaymentMethod::CRYPTO]);
        self::assertArrayHasKey('fields', $currencies['BTC'][CryptoFlexPaymentMethod::CRYPTO]);
        self::assertArrayHasKey('name', $currencies['BTC'][CryptoFlexPaymentMethod::CRYPTO]);
    }
}
