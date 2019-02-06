<?php
/**
 * Created by PhpStorm.
 * User: nikolay
 * Date: 22.11.18
 * Time: 12:05
 */

namespace pps\cryptoflex\tests\_data;

use yii\helpers\ArrayHelper;

class ApiTestData
{
    const PARTNER_ID = '11111111111111111111111111';
    const SECRET_KEY = 'AF4B5DE6-3468-424C-A922-C1DAD7CB4509';
    const CONFIRM_PAYOUT = 3;
    const CONFIRM_TRANSACTION = 2;
    const CONFIRM_WITHDRAW = 3;
    const WALLET_ADDRESS = 'dfafsage7874649asregf4a6g49';
    const FEE_LEVEL = 'low';
    const DENOMINATION = 1;

    const DATA = [
        'order' => [
            'request' => [
                'partner_id' => self::PARTNER_ID,
                'partner_invoice_id' => 'cryptoflex_1',
                'payout_address' => self::WALLET_ADDRESS,
                'callback' => 'http://test.com.ua',
                'crypto_currency' => 'BTC',
                'confirmations_trans' => self::CONFIRM_TRANSACTION,
                'confirmations_payout' => self::CONFIRM_PAYOUT,
                'fee_level' => self::FEE_LEVEL,
            ],
            'response' => [
                'data' => [
                    'invoice_id' => 'FGs1QhKcuYttefsfvRLYR66SPJYDtiWxPyiwN8hD',
                    'partner_invoice_id' => 'cryptoflex_1',
                    'crypto_currency' => 'BTC',
                    'address' => '0x5dc5e95a3076a0f5ef717df9f4b65459c7671241'
                ],
                'error_code' => 0,
                'result' => 1,
            ]
        ],
        'withdraw' => [
            'request' => [
                'partner_withdraw_id' => 'cryptoflex_wd_1',
                'wallet_address' => self::WALLET_ADDRESS,
                'requisites' => ['withdraw_address' => '11111111111111111111'],
                'crypto_currency' => 'BTC',
                'confirmations_withdraw' => self::CONFIRM_WITHDRAW,
                'amount' => '0.004500',
                'fee_level' => self::FEE_LEVEL,
            ],
            'response' => [
                'data' => [
                    'amount' => '0.004500',
                    'status' => 5,
                    'fee_value' => "None",
                    'withdraw_id' => 'xVMLgfLFJvDeNrjhe7v6wVgVz4X19vSCnH58hMFC',
                    'partner_withdraw_id' => 'cryptoflex_wd_1',
                    'crypto_currency' => 'BTC',
                    'withdraw_address' => '0x3fb37284478f03df2f473f54541ff10ecb66d3e4'
                ],
                'error_code' => 0,
                'result' => 1,
            ]
        ],
        'withdraw_status' => [
            'request' => [
                'wallet_address' => self::WALLET_ADDRESS,
                'crypto_currency' => 'BTC',
                'partner_withdraw_id' => 'cryptoflex_wd_1',
            ],
            'response' => [
                'data' => [
                    'amount' => '0.004500',
                    'status' => 5,
                    'fee_value' => "None",
                    'withdraw_id' => 'xVMLgfLFJvDeNrjhe7v6wVgVz4X19vSCnH58hMFC',
                    'partner_withdraw_id' => 'cryptoflex_wd_1',
                    'crypto_currency' => 'BTC',
                    'withdraw_address' => '0x3fb37284478f03df2f473f54541ff10ecb66d3e4'
                ],
                'error_code' => 0,
                'result' => 1,
            ]
        ],
        'wallet_create' => [
            'request' => [
                'partner_id' => self::PARTNER_ID,
                'crypto_currency' => 'BTC',
            ],
            'response' => [
                'data' => [
                    'wallet_address' => '0xe1a0af5f776d6d086fd9ffe8286fd2bea33a6323',
                ],
                'error_code' => 0,
                'result' => 1,
            ]
        ],
        'wallet_balance' => [
            'request' => [
                'wallet_address' => self::WALLET_ADDRESS,
                'crypto_currency' => 'BTC',
            ],
            'response' => [
                'data' => [
                    'wallet_address' => '0xe1a0af5f776d6d086fd9ffe8286fd2bea33a6323',
                    'balance' => '0.0056',
                    'crypto_currency' => 'BTC',
                ],
                'error_code' => 0,
                'result' => 1,
            ]
        ],
        'callback' => [
            'request' => [
                'invoice_id' => 'SxQ2LAdLV8kbZHNETgjiZzB5tzu4j9tFwjJmU1vR',
                'partner_invoice_id' => 'cryptoflex_1',
                'data_transit' => '2018-07-18 14:04:06',
                'data_receive' => '2018-09-04 13:39:46',
                'address' => '0x85a65527318c01e89189b8244e5462715bd9fe33',
                'payout_address' => '0x90f86243370dea297e573b57ab3c546b64c278f2',
                'amount' => '0.0099328',
                'crypto_currency' => 'BTC',
                'confirmations_trans' => '2',
                'confirmations_payout' => '3',
                'fee_level' => 'low',
                'fee_value' => '4.41e-05',
                'status' => '2',
                'transaction_id' => '0xa766231740051f52de9e6652db2e537ded1c671f56788285192583edfd928023',
                'sign' => 'b2aa28885b56b74f9eff8a5f934731ecec679b713b3b225c98db5c13d4924113',
                //'type' => 'crypto'
            ],
        ]
    ];

    /*
     * формирование подписи
    $key = [
                'invoice_id' => 'SxQ2LAdLV8kbZHNETgjiZzB5tzu4j9tFwjJmU1vR',
                'partner_invoice_id' => 'cryptoflex_1',
                'data_transit' => '2018-07-18 14:04:06',
                'data_receive' => '2018-09-04 13:39:46',
                'address' => '0x85a65527318c01e89189b8244e5462715bd9fe33',
                'payout_address' => '0x90f86243370dea297e573b57ab3c546b64c278f2',
                'amount' => '0.0099328',
                'crypto_currency' => 'BTC',
                'confirmations_trans' => '2',
                'confirmations_payout' => '3',
                'fee_level' => 'low',
                'fee_value' => '4.41e-05',
                'status' => '2',
                'transaction_id' => '0xa766231740051f52de9e6652db2e537ded1c671f56788285192583edfd928023',
    ];

    ksort($key);

    $stringToSign = implode(':', $key) . 'AF4B5DE6-3468-424C-A922-C1DAD7CB4509';

    echo hash('sha256', $stringToSign, false);
    */

    const ERROR_DATA = [
        'data' => 'none',
        'message' => 'Error happens',
        'error_code' => 2000,
        'result' => 0,
    ];

    /**
     * @param string $method
     * @param string $type
     * @param bool $asJson
     * @return array|false|string
     */
    public static function getData(string $method, string $type, bool $asJson = false)
    {
        if ($type === 'error') {
            $data = self::ERROR_DATA;
        } else {
            $data = ArrayHelper::getValue(self::DATA, $method . '.' . $type, []);
        }
        return $asJson ? json_encode($data) : $data;
    }
}