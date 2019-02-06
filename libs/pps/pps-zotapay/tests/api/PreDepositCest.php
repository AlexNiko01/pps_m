<?php
namespace pps\zotapay\tests\api;

use pps\zotapay\tests\ApiTester;

class PreDepositCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    // tests
    public function tryToTest(ApiTester $I)
    {
        $I->haveHttpHeader('Auth', 'hash');
        $I->haveHttpHeader('X-PPS-Time', 'hash');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendGET('pre-deposit', []);
        $I->seeResponseContains('{"result":"ok"}');
    }
}
