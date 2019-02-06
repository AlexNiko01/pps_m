<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 21.11.18
 * Time: 14:19
 */

use pps\cryptoflex\CryptoFlexApi\CryptoFlexConfig;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexEndpoints;
use pps\cryptoflex\CryptoFlexApi\CryptoFlexPaymentMethod;
use pps\cryptoflex\CryptoFlexApi\Responses\BaseResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoOrderResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoPayoutResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoPayoutStatusResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoWalletBalanceResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\CryptoWalletCreateResponse;
use pps\cryptoflex\CryptoFlexApi\Responses\crypto\NullResponse;

class BaseResponseTest extends \Codeception\Test\Unit
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
        $this->config->setPaymentWay(\pps\payment\Payment::WAY_DEPOSIT);
    }

    public function testGetPayoutResponse()
    {
        $this->config->setApiMode(CryptoFlexConfig::API_MODE_PAYOUT);
        $responseData = '{"data": { "amount": "0.0015", "status": "5", "fee_value": "None", '
            . '"withdraw_id": "xVMLgfLFJvDeNrjhe7v6wVgVz4X19vSCnH58hMFC", "crypto_currency": "ETH",'
            . ' "withdraw_address": "0x3fb37284478f03df2f473f54541ff10ecb66d3e4",'
            . ' "partner_withdraw_id": "Test_for_apidoc1" }, "error_code": 0, "result": 1}';
        $response = BaseResponse::getResponse($this->config, json_decode($responseData, true));
        $this->assertAttributeEquals(0, 'error_code', $response);
        $this->assertAttributeEquals(1, 'result', $response);
        $this->assertInstanceOf(CryptoPayoutResponse::class, $response->data);
        $this->assertAttributeEquals(0.0015, 'amount', $response->data);
        $this->assertAttributeEquals('5', 'status', $response->data);
        $this->assertAttributeEquals(0, 'fee_value', $response->data);
        $this->assertAttributeEquals('xVMLgfLFJvDeNrjhe7v6wVgVz4X19vSCnH58hMFC', 'withdraw_id', $response->data);
        $this->assertAttributeEquals('ETH', 'crypto_currency', $response->data);
        $this->assertAttributeEquals('0x3fb37284478f03df2f473f54541ff10ecb66d3e4', 'withdraw_address', $response->data);
        $this->assertAttributeEquals('Test_for_apidoc1', 'partner_withdraw_id', $response->data);

        $this->assertArrayHasKey('status', $response->data->toArray());
        $this->assertArrayHasKey('data', $response->getResponseBody());
    }

    public function testGetOrderResponse()
    {
        $responseData = '{"data": { "invoice_id": "FGs1QhKcuYttefsfvRLYR66SPJYDtiWxPyiwN8hD", '
            . '"partner_invoice_id": "Test_for_apidoc", "crypto_currency": "ETH", '
            . '"address": "0x5dc5e95a3076a0f5ef717df9f4b65459c7671241" }, "error_code": 0, "result": 1}';
        $response = BaseResponse::getResponse($this->config, json_decode($responseData, true));
        $this->assertAttributeEquals(0, 'error_code', $response);
        $this->assertAttributeEquals(1, 'result', $response);
        $this->assertInstanceOf(CryptoOrderResponse::class, $response->data);
        $this->assertAttributeEquals('FGs1QhKcuYttefsfvRLYR66SPJYDtiWxPyiwN8hD', 'invoice_id', $response->data);
        $this->assertAttributeEquals('Test_for_apidoc', 'partner_invoice_id', $response->data);
        $this->assertAttributeEquals('ETH', 'crypto_currency', $response->data);
        $this->assertAttributeEquals('0x5dc5e95a3076a0f5ef717df9f4b65459c7671241', 'address', $response->data);
    }

    public function testGetPayoutStatusResponse()
    {
        $this->config->setApiMode(CryptoFlexConfig::API_MODE_PAYOUT_STATUS);
        $responseData = '{"data": { "amount": "0.0015", "status": "5", "fee_value": "None", '
            . '"withdraw_id": "xVMLgfLFJvDeNrjhe7v6wVgVz4X19vSCnH58hMFC", "crypto_currency": "ETH",'
            . ' "withdraw_address": "0x3fb37284478f03df2f473f54541ff10ecb66d3e4",'
            . ' "partner_withdraw_id": "Test_for_apidoc1" }, "error_code": 0, "result": 1}';
        $response = BaseResponse::getResponse($this->config, json_decode($responseData, true));
        $this->assertAttributeEquals(0, 'error_code', $response);
        $this->assertAttributeEquals(1, 'result', $response);
        $this->assertInstanceOf(CryptoPayoutStatusResponse::class, $response->data);
        $this->assertAttributeEquals(0.0015, 'amount', $response->data);
        $this->assertAttributeEquals('5', 'status', $response->data);
        $this->assertAttributeEquals(0, 'fee_value', $response->data);
        $this->assertAttributeEquals('xVMLgfLFJvDeNrjhe7v6wVgVz4X19vSCnH58hMFC', 'withdraw_id', $response->data);
        $this->assertAttributeEquals('ETH', 'crypto_currency', $response->data);
        $this->assertAttributeEquals('0x3fb37284478f03df2f473f54541ff10ecb66d3e4', 'withdraw_address', $response->data);
        $this->assertAttributeEquals('Test_for_apidoc1', 'partner_withdraw_id', $response->data);
    }

    public function testGetWalletBalanceResponse()
    {
        $this->config->setApiMode(CryptoFlexConfig::API_MODE_WALLET_BALANCE);
        $responseData = '{ "data": { "wallet_address": "0xe1a0af5f776d6d086fd9ffe8286fd2bea33a6323", "balance": "54",'
            . ' "crypto_currency": "ETH" }, "error_code": 0, "result": 1}';
        $response = BaseResponse::getResponse($this->config, json_decode($responseData, true));
        $this->assertAttributeEquals(0, 'error_code', $response);
        $this->assertAttributeEquals(1, 'result', $response);
        $this->assertInstanceOf(CryptoWalletBalanceResponse::class, $response->data);
        $this->assertAttributeEquals(54.00, 'balance', $response->data);
        $this->assertAttributeEquals('0xe1a0af5f776d6d086fd9ffe8286fd2bea33a6323', 'wallet_address', $response->data);
        $this->assertAttributeEquals('ETH', 'crypto_currency', $response->data);
    }

    public function testWalletCreateResponse()
    {
        $this->config->setApiMode(CryptoFlexConfig::API_MODE_WALLET_CREATE);
        $responseData = '{  "data": { "wallet_address": "0xe1a0af5f776d6d086fd9ffe8286fd2bea33a6323" },'
            . ' "error_code": 0, "result": 1}';
        $response = BaseResponse::getResponse($this->config, json_decode($responseData, true));
        $this->assertAttributeEquals(0, 'error_code', $response);
        $this->assertAttributeEquals(1, 'result', $response);
        $this->assertInstanceOf(CryptoWalletCreateResponse::class, $response->data);
        $this->assertAttributeEquals('0xe1a0af5f776d6d086fd9ffe8286fd2bea33a6323', 'wallet_address', $response->data);
    }

    public function testErrorResponse()
    {
        $this->config->setApiMode(CryptoFlexConfig::API_MODE_WALLET_CREATE);
        $responseData = '{"data": "none", "message":'
            . ' "Invalid sign 2959dca127879d09ebe02d4d369a9c5fc534a4791fdd9d12409b2fd9b01415da",'
            . ' "error_code": 2000, "result": 0}';
        $response = BaseResponse::getResponse($this->config, json_decode($responseData, true));
        $this->assertAttributeEquals(2000, 'error_code', $response);
        $this->assertAttributeEquals(0, 'result', $response);
        $this->assertAttributeEquals(
            'Invalid sign 2959dca127879d09ebe02d4d369a9c5fc534a4791fdd9d12409b2fd9b01415da',
            'message',
            $response
        );
        $this->assertInstanceOf(NullResponse::class, $response->data);
    }
}
