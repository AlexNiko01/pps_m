<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 22.12.18
 * Time: 14:40
 */

namespace pps\zotapay\tests\_data;

use function GuzzleHttp\Psr7\build_query;
use yii\helpers\ArrayHelper;

class ApiTestData
{
    const CLIENT_LOGIN = 'login';
    const CONTROL_KEY = 'AF4B5DE6-3468-424C-A922-C1DAD7CB4509';
    const ENDPOINT_ID = 3;
    const CONFIRM_TRANSACTION = '13237';


    const DATA = [
        'order' => [
            'request' => [
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
            ],
            'response' => [
                'type' => 'async-form-response',
                'paynet-order-id' => 'external_id_111111',
                'merchant-order-id' => 'test_id',
                'serial-number' => '00000000-0000-0000-0000-0000005b2a8a',
                'redirect-url' => 'http://redirect.com/',
            ],
            'errorResponse' => [
                'type' => 'error',
                'error-message' => 'Unknown error',
                'error-code' => 'Unknown error',
            ]
        ],
        'withdraw' => [
            'request' => [
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
            ],
            'response' => [
                'type' => 'async-form-response',
                'paynet-order-id' => 'external_id_111111',
                'merchant-order-id' => 'test_id',
                'serial-number' => '00000000-0000-0000-0000-0000005b2a8a',
            ],
            'errorResponse' => [
                'type' => 'error',
                'error-message' => 'Unknown error',
                'error-code' => 'Unknown error',
            ]
        ],
        'withdraw_status' => [
            'request' => [
                'client_orderid' => 'safafag23542345ttg',
                'orderid' => '1111111111111111111111111111',
                'by_request_sn' => '00000000-0000-0000-0000-0000005b2a8a',
            ],
            'response' => [
                'type' => 'status-response',
                'status' => 'approved',
                'amount' => 1,
                'paynet-order-id' => 'external_id_111111',
                'merchant-order-id' => 'test_id',
                'phone' => '0952635987',
                'serial-number' => '00000000-0000-0000-0000-0000005b5044',
                'last-four-digits' => '1236',
                'bin' => '520306',
                'card-type' => 'MASTERCARD',
                'gate-partial-reversal' => 'enabled',
                'gate-partial-capture' => 'enabled',
                'transaction-type' => 'sale',
                'processor-rrn' => '510321889824',
                'processor-tx-id' => 'PNTEST-159884',
                'receipt-id' => 'a5061379-6ff5-3565-a9ba-1b8a814d04f6',
                'name' => 'TEST HOLDER',
                'cardholder-name' => 'TEST HOLDER',
                'card-exp-month' => '1',
                'card-exp-year' => '2016',
                'card-hash-id' => '',
                'email' => 'john.smith@gmail.com',
                'bank-name' => 'CITIBANK',
                'paynet-processing-date' => '2015-04-09 17:14:26 MSK',
                'approval-code' => '242805',
                'order-stage' => 'sale_approved',
                'descriptor' => 'test-usd',
                'by-request-sn' => '00000000-0000-0000-0000-0000005b2a8a',
                'verified-3d-status' => 'AUTHENTICATED',
                'verified-rsc-status' => 'AUTHENTICATED',
            ],
            'errorResponse' => [
                'type' => 'error',
                'error-message' => 'Unknown error',
                'error-code' => 'Unknown error',
            ]
        ],
        'callback' => [
            'request' => [
                'status' => 'approved',
                'merchant_order' => 'invoice-1',
//            'client_orderid',
                'orderid' => '123',
//            'type' => '',
                'amount' => 100,
//            'descriptor',
//            'error_code',
//            'error_message',
//            'name',
//            'email',
//            'approval-code',
//            'last-four-digits',
//            'bin',
                'card-type' => 'VISA',
//            'gate-partial-reversal',
//            'gate-partial-capture',
//            'reason-code',
//            'processor-rrn',
//            'comment',
//            'rapida-balance',
                'control' => '5bc8ee48f9ba37c0fd1e0b052a9bc105c6df87e1',
                //        'merchantdata',

            ],
        ]
    ];

    /**
     * @param string $method
     * @param string $type
     * @param bool $asJson
     * @return array|false|string
     */
    public static function getData(string $method, string $type, bool $asQueryParams = false)
    {
        $data = ArrayHelper::getValue(self::DATA, $method . '.' . $type, []);
        return $asQueryParams ? build_query($data) : $data;
    }
}
