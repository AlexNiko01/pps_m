<?php

namespace pps\paysafecard;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $customer_id;
    public $key;

    public function attributeLabels():array
    {
        return [
            'customer_id' => 'Customer ID',
            'key' => 'Key',
        ];
    }

    public function rules():array
    {
        return [
            [['key', 'customer_id'], 'required'],
            [['key', 'customer_id'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'customer_id' => 'text',
            'key' => 'text',
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
            'public_key' => 'customer_id',
            'private_key' => 'key',
        ];

        return $keys[$key] ?? null;
    }
}