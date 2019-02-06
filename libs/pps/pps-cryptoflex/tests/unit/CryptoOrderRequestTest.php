<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 20.11.18
 * Time: 16:13
 */
namespace pps\cryptoflex\tests\unit;

use Codeception\Test\Unit;
use GuzzleHttp\Psr7\Request;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexEndpoints;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\Exceptions\CryptoFlexValidationException;
use pps\cryptoflex\CryptoFlexApi\Requests\crypto\CryptoOrderRequest;

class CryptoOrderRequestTest extends Unit
{
    /**
     * @var CryptoFlexConfig $config
    */
    protected $config;

    protected function _before()
    {
        $this->config = new CryptoFlexConfig(
            CryptoFlexPaymentMethod::CRYPTO,
            CryptoFlexEndpoints::TEST
        );
        $this->config->setPartnerId('11111111111111111111111111');
        $this->config->setSecretKey('AF4B5DE6-3468-424C-A922-C1DAD7CB4509');
        $this->config->setConfirmationsTrans(2);
        $this->config->setConfirmationsPayout(3);
        $this->config->setWalletAddress('dfafsage7874649asregf4a6g49');
        $this->config->setFeeLevel('low');
        $this->config->setDenomination(1);

        $this->config->setApiMode(CryptoFlexConfig::API_MODE_ORDER);
    }

    public function testSuccessCreateOrderRequest()
    {
        /**
         * @var CryptoOrderRequest $order
        */
        $order = \pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest::getRequest($this->config, [
            'partner_id' => $this->config->getPartnerId(),
            'partner_invoice_id' => 'cryptoflex_' . time(),
            'payout_address' => $this->config->getWalletAddress(),
            'callback' => 'http://test.com.ua',
            'crypto_currency' => 'BTC',
            'confirmations_trans' => $this->config->getConfirmationsTrans(),
            'confirmations_payout' => $this->config->getConfirmationsPayout(),
            'fee_level' => $this->config->getFeeLevel(),
        ]);
        $this->assertInstanceOf(CryptoOrderRequest::class, $order);
        $this->assertAttributeEquals($this->config->getPartnerId(), 'partner_id', $order);
        $this->assertStringStartsWith('cryptoflex_', $order->partner_invoice_id);
        $this->assertAttributeEquals($this->config->getWalletAddress(), 'payout_address', $order);
        $this->assertAttributeEquals('http://test.com.ua', 'callback', $order);
        $this->assertAttributeEquals('BTC', 'crypto_currency', $order);
        $this->assertAttributeEquals($this->config->getConfirmationsTrans(), 'confirmations_trans', $order);
        $this->assertAttributeEquals($this->config->getConfirmationsPayout(), 'confirmations_payout', $order);
        $this->assertAttributeEquals($this->config->getFeeLevel(), 'fee_level', $order);
        $this->assertAttributeNotEmpty('sign', $order);
        $this->assertAttributeNotEmpty('timestamp', $order);

        $this->assertEquals(CryptoFlexPaymentMethod::CRYPTO, $order->getMethod());
        $this->assertArrayHasKey('sign', $order->getRequestBody());

        $this->assertInstanceOf(Request::class, $order->prepareRequest());
    }

    public function testFailParamsMissing()
    {
        $this->expectException(CryptoFlexValidationException::class);
        /**
         * @var CryptoOrderRequest $order
         */
        \pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest::getRequest($this->config, [
            'partner_id' => $this->config->getPartnerId(),
            'partner_invoice_id' => 'cryptoflex_' . time(),
            'payout_address' => $this->config->getWalletAddress(),
            //'callback' => 'http://test.com.ua',
            'crypto_currency' => 'BTC',
            'confirmations_trans' => $this->config->getConfirmationsTrans(),
            'confirmations_payout' => $this->config->getConfirmationsPayout(),
            'fee_level' => 'low',
        ]);
    }
}
