<?php

namespace pps\nixmoney;

use pps\payment\IModel;
use pps\payment\MultiSettingsInterface;
use pps\payment\PaymentModel;

/**
 * Class Model
 * @package pps\nixmoney
 */
class Model extends PaymentModel implements IModel, MultiSettingsInterface
{
    public $account_id;
    public $pass_phrase;
    public $account;


    public function attributeLabels():array
    {
        return [
            'account_id' => 'Account ID',
            'pass_phrase' => 'Pass phrase',
            'account' => 'Account',
        ];
    }

    public function rules():array
    {
        return [
            [['account_id', 'pass_phrase', 'account'], 'required', 'on' => PaymentModel::SCENARIO_REQUIRED],
            [['account_id', 'pass_phrase', 'account'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'account_id' => 'text',
            'pass_phrase' => 'text',
            'account' => 'text',
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
            'public_key' => 'account_id',
            'private_key' => 'pass_phrase',
            'field3' => 'account',
        ];

        return $keys[$key] ?? null;
    }

    public function currencies(): array
    {
        return ['USD', 'EUR', 'BTC', 'LTC', 'CRT', 'FTC', 'PPC', 'DOGE', 'CLR', 'XBL', 'SVC', 'MVR'];
    }
}