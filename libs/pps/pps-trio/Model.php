<?php

namespace pps\trio;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $shop_id;
    public $secret_key;


    public function attributeLabels():array
    {
        return [
            'shop_id' => 'Shop ID',
            'secret_key' => 'Secret key'
        ];
    }

    public function rules():array
    {
        return [
            [['shop_id', 'secret_key'], 'required'],
            [['shop_id', 'secret_key'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'shop_id' => 'number',
            'secret_key' => 'text'
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
            'public_key' => 'shop_id',
            'private_key' => 'secret_key'
        ];

        return $keys[$key] ?? null;
    }
}