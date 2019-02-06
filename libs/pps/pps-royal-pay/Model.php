<?php

namespace pps\royalpay;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $auth_key;
    public $secret_key;


    public function attributeLabels():array
    {
        return [
            'auth_key' => 'Auth key',
            'secret_key' => 'Secret key'
        ];
    }

    public function rules():array
    {
        return [
            [['secret_key', 'auth_key'], 'required'],
            [['secret_key', 'auth_key'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'auth_key' => 'text',
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
            'public_key' => 'auth_key',
            'private_key' => 'secret_key'
        ];

        return $keys[$key] ?? null;
    }
}