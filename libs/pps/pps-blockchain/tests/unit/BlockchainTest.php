<?php

use pps\blockchain\Blockchain;
use yii\base\InvalidParamException;
use pps\payment\Payment;
use yii\web\Response;


class BlockchainTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _before()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }


    public function testCreateInstanceEmptyData()
    {
        $this->setExpectedException(InvalidParamException::class);
        $this->expectExceptionMessage('"contract data" are empty');
        new Blockchain([]);
    }

    public function testCreateInstanceIssetWallet()
    {
        $this->setExpectedException(InvalidParamException::class);
        $this->expectExceptionMessage('"wallet_url" do not isset in main config');
        new Blockchain(['param' => 'test']);
    }

    public function testCreateInstanceIssetXpub()
    {
        $this->setExpectedException(InvalidParamException::class);
        $this->expectExceptionMessage('"xpub" empty');
        new Blockchain(['wallet_url' => 'test']);
    }

    public function testCreateInstanceIssetApiKey()
    {
        $this->setExpectedException(InvalidParamException::class);
        $this->expectExceptionMessage('"api_key" empty');
        new Blockchain(['wallet_url' => 'test', 'xpub' => 'test']);
    }

    public function testCreateInstanceIssetAll()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);
        $this->assertTrue($instance instanceof \pps\payment\Payment);
    }

    public function testCreateInstanceBadConfirmations()
    {
        $this->setExpectedException(InvalidParamException::class);
        $this->expectExceptionMessage('"confirmations" should be integer');
        new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test', 'confirmations' => '123']);
    }

    public function testCreateInstanceBadFee()
    {
        $this->setExpectedException(InvalidParamException::class);
        $this->expectExceptionMessage('"fee" should be integer');
        new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test', 'fee' => '5000']);
    }

    public function testInstanceSuccess()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test', 'confirmations' => 10, 'fee' => 8000]);
        $this->assertTrue($instance instanceof \pps\payment\Payment);
    }

    public function testPreInvoiceSuccess()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);
        $result = $instance->preInvoice(['currency' => 'CUR', 'payment_method' => 'bad', 'amount' => 0.05]);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Method doesn't supported");
    }

    public function testInvoiceSuccess()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);

        $transaction = new stdClass();
        $transaction->currency = 'CUR';
        $transaction->payment_method = 'bad';
        $transaction->amount = '0.00001';

        $requests = new stdClass();

        $params = [
            'transaction' => $transaction,
            'requests' => $requests
        ];

        $result = $instance->invoice($params);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Method doesn't supported");
    }

    public function testReceiveUndefinedTransaction()
    {
        $this->setExpectedException(\PHPUnit\Framework\Exception::class);
        $this->expectExceptionMessage('Undefined index: transaction');

        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);
        $params = [];
        $instance->receive($params);
    }

    public function testReceiveUndefinedReceive_data()
    {
        $this->setExpectedException(\PHPUnit\Framework\Exception::class);
        $this->expectExceptionMessage('Undefined index: receive_data');

        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);
        $params = [
            'transaction' => 'tr'
        ];
        $instance->receive($params);
    }

    public function testReceive()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);

        $tr = new class {
            public $status;
            public $amount;
            public $refund;
            public $buyer_id;
            public function save()
            {
                echo __LINE__ . PHP_EOL;
            }
            public function delete()
            {
                echo __LINE__ . PHP_EOL;
            }
        };

        $tr->status = Payment::STATUS_SUCCESS;
        $tr->amount = 0.05;
        $tr->refund = 0.04;
        $tr->buyer_id = 20;

        $data = [
            'transaction' => $tr,
            'receive_data' => [
                'transaction_hash' => 'e7ae422d57bfb140d191d6d22a1ba6e91f635d09e675bec5dbc6a41f3690855b',
                'sign' => 'sign',
                'value' => 1000,
                'confirmations' => 2,
                'address' => '1L9cmZyYrihAZsFQ5YLWUbq42xHJ79vk9D',
            ],
        ];

        $result = $instance->receive($data);
        $this->assertTrue($result);

        $tr->status = Payment::STATUS_PENDING;

        $result = $instance->receive($data);
        $this->assertFalse($result);

        $data['receive_data']['sign'] = md5("test:{$tr->buyer_id}:test");

        $result = $instance->receive($data);
        $this->assertTrue($result);
        $this->assertEquals( Payment::STATUS_DSPEND, $tr->status);

        $data['receive_data']['transaction_hash'] = 'b6f6991d03df0e2e04dafffcd6bc418aac66049e2cd74b80f14ac86db1e3f0da';

        $tr->status = Payment::STATUS_PENDING;

        $result = $instance->receive($data);
        $this->assertFalse($result);
        $this->assertEquals( Payment::STATUS_UNCONFIRMED, $tr->status, "status {$tr->status}");

        $data['receive_data']['confirmations'] = 6;

        $result = $instance->receive($data);
        $this->assertTrue($result);
        $this->assertEquals( Payment::STATUS_MISPAID, $tr->status, "status {$tr->status}");

        $data['receive_data']['value'] = $tr->amount * Blockchain::TO_SAT;
        $tr->status = Payment::STATUS_PENDING;

        $result = $instance->receive($data);
        $this->assertTrue($result);
        $this->assertEquals( Payment::STATUS_SUCCESS, $tr->status, "status {$tr->status}");
    }

    public function testSuccessAnswer()
    {
        $this->assertEquals('*ok*', Blockchain::getSuccessAnswer());
    }

    public function testResponceFormat()
    {
        $this->assertEquals(Response::FORMAT_HTML, Blockchain::getResponseFormat());
    }

    public function testGetTransactionID()
    {
        $this->assertFalse(Blockchain::getTransactionID(['invoice_id' => 100]));
    }

    public function testGetTransactionQuery()
    {
        $this->assertEquals([], Blockchain::getTransactionQuery(['invoice_id' => 100]));
    }

    public function testSupportedCurrencies()
    {
        $currencies = Blockchain::getSupportedCurrencies();

        $this->assertTrue(is_array($currencies));
        $this->assertArrayHasKey('BTC', $currencies);
        $this->assertArrayHasKey('bitcoin', $currencies['BTC']);
        $this->assertArrayHasKey('name', $currencies['BTC']['bitcoin']);
        $this->assertArrayHasKey('fields', $currencies['BTC']['bitcoin']);
        $this->assertTrue(is_array($currencies['BTC']['bitcoin']['fields']));
    }

    public function testPreWithdraw()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);

        $result = $instance->preWithDraw(['currency' => 'CUR', 'payment_method' => 'bad', 'amount' => 0.05]);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Currency 'CUR' does not supported");

        $result = $instance->preWithDraw(['currency' => 'BTC', 'payment_method' => 'bad', 'amount' => 0.05]);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Method 'bad' does not exists");

        $result = $instance->preWithDraw(['currency' => 'BTC', 'payment_method' => 'bitcoin', 'amount' => 0.00005]);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Amount should to be more than '0.00015' and less than '0.1'");

        $result = $instance->preWithDraw(['currency' => 'BTC', 'payment_method' => 'bitcoin', 'amount' => 0.001]);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Error getting wallet balance");

        // Then we should use real account
    }

    public function testWithdraw()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);

        $transaction = new class {
            private $_data = [];

            public function __set($name, $value)
            {
                $this->_data[$name] = $value;
            }

            public function __get($name)
            {
                return $this->_data[$name] ?? null;
            }

            public function save()
            {
                if ($this->amount == 0.099) {
                    throw new \yii\db\Exception('error');
                } else {
                    return true;
                }
            }

            public function delete() {}
        };
        $transaction->id = 'CUR';
        $transaction->currency = 'CUR';
        $transaction->payment_method = 'bad';
        $transaction->amount = '0.00001';

        $requests = [];

        $params = [
            'transaction' => $transaction,
            'requests' => $requests
        ];

        $result = $instance->withDraw($params);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Currency 'CUR' does not supported");

        $transaction->currency = 'BTC';

        $result = $instance->withDraw($params);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Method 'bad' does not exists");

        $transaction->payment_method = 'bitcoin';

        $result = $instance->withDraw($params);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Amount should to be more than '0.00015' and less than '0.1'");

        $transaction->amount = 0.099;

        $result = $instance->withDraw($params);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "transaction_id is not unique");

        $transaction->amount = 0.098;

        $params['requests'] = [
            'm_out' => new class {
                public function save() {}
            },
            'merchant' => ''
        ];

        $result = $instance->withDraw($params);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($result['status'], 'error');
        $this->assertEquals($result['message'], "Account index not found");

        // Then for testing needs real account
    }

    public function testGetStatus()
    {
        $instance = new Blockchain(['wallet_url' => 'test', 'xpub' => 'test', 'api_key' => 'test']);

        $tr = new class {
            public $merchant_transaction_id;
            public $status;
            public $comment;
            public $currency;
            public $way;
            public $amount;
            public $refund;
            public $receive;
            public $external_id;
        };

        $tr->way = 'deposit';
        $tr->amount = 10;
        $tr->receive = 11;
        $tr->refund = 9;

        $result = $instance->getStatus($tr);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('transaction_id', $result['data']);
        $this->assertArrayHasKey('status', $result['data']);
        $this->assertArrayHasKey('comment', $result['data']);
        $this->assertArrayHasKey('currency', $result['data']);
        $this->assertArrayHasKey('way', $result['data']);
        $this->assertArrayHasKey('amount_buyer', $result['data']);
        $this->assertArrayHasKey('amount_merchant', $result['data']);

        $this->assertEquals($tr->amount, $result['data']['amount_buyer']);
        $this->assertEquals($tr->refund, $result['data']['amount_merchant']);

        $tr->way = 'withdraw';
        $tr->external_id = -1;
        $result = $instance->getStatus($tr);

        $this->assertEquals($tr->receive, $result['data']['amount_buyer']);
        $this->assertEquals($tr->amount, $result['data']['amount_merchant']);
    }

    // Method updateStatus is very slow (min 11 sec for finish)
}