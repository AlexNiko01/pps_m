<?php

namespace pps\bitgo;

use pps\payment\IModel;
use pps\payment\MultiSettingsInterface;
use pps\payment\PaymentModel;

/**
 * Class Model
 * @package pps\bitgo
 */
class Model extends PaymentModel implements IModel, MultiSettingsInterface
{
    public $access_token;
    public $wallet_id;
    public $password;


    public function attributeLabels(): array
    {
        return [
            'access_token' => 'Access token',
            'wallet_id' => 'Wallet ID (same)',
            'password' => 'Password (same)',
        ];
    }

    public function rules(): array
    {
        return [
            [['access_token', 'wallet_id', 'password'], 'required', 'on' => PaymentModel::SCENARIO_REQUIRED],
            [['access_token', 'wallet_id', 'password'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'wallet_id' => 'text',
            'access_token' => 'password',
            'password' => 'password',
        ];
    }

    /**
     * Transformation key from inside API to key from payment system
     * @param string $key
     * @return string|int|null
     */
    public function transformApiKey(string $key)
    {
        $keys = [
            'public_key' => 'wallet_id',
            'private_key' => 'access_token',
            'field3' => 'password',
        ];

        return $keys[$key] ?? null;
    }

    /**
     * @return array
     */
    public function currencies(): array
    {
        return [
            'BTC',
            'BCH',
            'BTG',
            'ETH',
            'LTC',
            'XRP',
            'RMG',
            'BAT',
            'BRD',
            'CVC',
            'FUN',
            'GNT',
            'KNC',
            'MKR',
            'NMR',
            'OMG',
            'PAY',
            'QRL',
            'REP',
            'RDN',
            'WAX',
            'ZIL',
            'ZRX',
            'TBTC',
            'TBCH',
            'TETH',
            'TERC',
            'TLTC',
            'TXRP',
            'TMRG',
        ];
    }
}