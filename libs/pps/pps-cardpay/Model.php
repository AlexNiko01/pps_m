<?php

namespace pps\cardpay;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $wallet_id;
    public $secret_word;
    public $login;
    public $password;

    public function attributeLabels():array
    {
        return [
            'wallet_id' => 'Wallet ID',
            'secret_word' => 'Secret word',
            'login' => 'Login',
            'password' => 'Password',
        ];
    }

    public function rules():array
    {
        return [
            [['secret_word', 'wallet_id', 'login', 'password'], 'required'],
            [['secret_word', 'wallet_id', 'login', 'password'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'wallet_id' => 'number',
            'secret_word' => 'text',
            'login' => 'text',
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
            'private_key' => 'secret_word',
            'field3' => 'login',
            'field4' => 'password',
        ];

        return $keys[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getRequiredParams()
    {
        return ['public_key', 'private_key', 'field3', 'field4'];
    }
}