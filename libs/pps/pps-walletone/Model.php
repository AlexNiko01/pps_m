<?php

namespace pps\walletone;

use pps\payment\IModel;

/**
 * Class Model
 * @package pps\walletone
 */
class Model extends \yii\base\Model implements IModel
{
    public $merchant_id;
    public $secret_key;

    public function attributeLabels():array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'secret_key' => 'Secret key',
        ];
    }

    public function rules():array
    {
        return [
            [['secret_key', 'merchant_id'], 'required'],
            [['secret_key', 'merchant_id'], 'trim'],
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
            'secret_key' => 'text',
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
            'private_key' => 'secret_key',
        ];

        return $keys[$key] ?? null;
    }
}