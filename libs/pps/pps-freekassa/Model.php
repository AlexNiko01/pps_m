<?php

namespace pps\freekassa;

use pps\payment\IModel;
use pps\payment\MultiSettingsInterface;
use pps\payment\PaymentModel;

class Model extends PaymentModel implements IModel, MultiSettingsInterface
{
    public $wallet;
    public $api_key;
    public $merchant_id;
    public $secret_word1;
    public $secret_word2;


    public function attributeLabels(): array
    {
        return [
            'wallet' => 'Merchant wallet',
            'api_key' => 'API key',
            'merchant_id' => 'Merchant id (crypto don\'t need it)',
            'secret_word1' => 'Secret word N1 (crypto don\'t need it)',
            'secret_word2' => 'Secret word N2 (crypto don\'t need it)',
        ];
    }

    public function rules(): array
    {
        return [
            [
                ['api_key', 'wallet', 'merchant_id', 'secret_word1', 'secret_word2'],
                'required', 'on' => self::SCENARIO_REQUIRED
            ],
            [['api_key', 'wallet', 'merchant_id', 'secret_word1', 'secret_word2'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'wallet' => 'text',
            'api_key' => 'text',
            'merchant_id' => 'number',
            'secret_word1' => 'password',
            'secret_word2' => 'password'
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
            'public_key' => 'wallet',
            'private_key' => 'api_key',
            'field3' => 'merchant_id',
            'field4' => 'secret_word1',
            'field5' => 'secret_word2'
        ];

        return $keys[$key] ?? null;
    }

    public function requiredApiKeys()
    {
        return ['public_key', 'private_key', 'field3', 'field4', 'field5'];
    }

    public function currencies(): array
    {
        return ['RUB', 'USD', 'EUR', 'UAH', 'BTC', 'ETH', 'LTC'];
    }
}