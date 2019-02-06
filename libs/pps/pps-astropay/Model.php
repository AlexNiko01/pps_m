<?php

namespace pps\astropay;

use pps\payment\IModel;

/**
 * Class Model
 * @package pps\qiwi
 */
class Model extends \yii\base\Model implements IModel
{
    public $x_login;
    public $x_trans_key;
    public $secret_key;


    public function attributeLabels():array
    {
        return [
            'x_login' => 'X_LOGIN',
            'x_trans_key' => 'X_TRANS_KEY',
            'secret_key' => 'Secret key',
        ];
    }

    public function rules():array
    {
        return [
            [['x_login', 'x_trans_key', 'secret_key'], 'required'],
            [['x_login', 'x_trans_key', 'secret_key'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'x_login' => 'text',
            'x_trans_key' => 'text',
            'secret_key' => 'password',
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
            'public_key' => 'x_login',
            'private_key' => 'secret_key',
            'field3' => 'x_trans_key',
        ];

        return $keys[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getRequiredParams(): array
    {
        return ['public_key', 'private_key', 'field3'];
    }
}