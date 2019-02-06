<?php

namespace pps\cryptonator;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $merchant_id;
    public $secret_key;

    public function attributeLabels():array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'secret_key' => 'Secret key'
        ];
    }

    public function rules():array
    {
        return [
            [['merchant_id', 'secret_key'], 'required'],
            [['merchant_id', 'secret_key'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'merchant_id' => 'text',
            'secret_key' => 'password'
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
            'public_key' => 'merchant_id',
            'private_key' => 'secret_key'
        ];

        return $keys[$key] ?? null;
    }
}