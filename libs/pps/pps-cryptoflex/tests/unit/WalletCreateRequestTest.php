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
use pps\cryptoflex\CryptoFlexApi\Requests\crypto\CryptoWalletCreateRequest;

class WalletCreateRequestTest extends Unit
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
        $this->config->setConfirmationsWithdraw(2);
        $this->config->setWalletAddress('dfafsage7874649asregf4a6g49');
        $this->config->setFeeLevel('low');
        $this->config->setDenomination(1);

        $this->config->setApiMode(CryptoFlexConfig::API_MODE_WALLET_CREATE);
    }

    public function testSuccessCreateOrderRequest()
    {
        /**
         * @var CryptoWalletCreateRequest $payout
        */
        $payout = \pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest::getRequest($this->config, [
            'partner_id' => $this->config->getPartnerId(),
            'crypto_currency' => 'BTC',
        ]);
        $this->assertInstanceOf(CryptoWalletCreateRequest::class, $payout);
        $this->assertAttributeEquals($this->config->getPartnerId(), 'partner_id', $payout);
        $this->assertAttributeEquals('BTC', 'crypto_currency', $payout);

        $this->assertAttributeNotEmpty('sign', $payout);
        $this->assertAttributeNotEmpty('timestamp', $payout);

        $this->assertArrayHasKey('sign', $payout->getRequestBody());

        $this->assertInstanceOf(Request::class, $payout->prepareRequest());
    }

    public function testFailParamsMissing()
    {
        $this->expectException(CryptoFlexValidationException::class);
        /**
         * @var CryptoOrderRequest $order
         */
        \pps\cryptoflex\CryptoFlexApi\Requests\BaseRequest::getRequest($this->config, [
            'partner_id' => $this->config->getPartnerId(),
            'crypto_currency' => 'BT',
        ]);
    }
}
