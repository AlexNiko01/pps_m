<?php

namespace pps\skrill;

use pps\payment\IModel;

/**
 * Class Model
 * @package pps\skrill
 */
class Model extends \yii\base\Model implements IModel
{
    public $merchant_id;
    public $email;
    public $password;
    public $secret_word;

    public function attributeLabels():array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'email' => 'E-mail',
            'password' => 'Password',
            'secret_word' => 'Secret word',
        ];
    }

    public function rules():array
    {
        return [
            [['merchant_id', 'email', 'password', 'secret_word'], 'required'],
            [['merchant_id', 'email', 'password', 'secret_word'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'merchant_id' => 'number', 
            'email' => 'text',
            'password' => 'text',
            'secret_word' => 'text',
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
            'public_key' => 'email',
            'private_key' => 'password',
            'field3' => 'secret_word',
            'field4' => 'merchant_id',
        ];

        return $keys[$key] ?? null;
    }
}