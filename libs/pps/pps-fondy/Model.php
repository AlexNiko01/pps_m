<?php

namespace pps\fondy;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $merchant_id;
    public $password_pay;
    public $password_credit;

    public function attributeLabels():array
    {
        return [
            'merchant_id' => 'Merchant ID',
            'password_pay' => 'Pay password',
            'password_credit' => 'Credit password'
        ];
    }

    public function rules():array
    {
        return [
            [['merchant_id', 'password_pay', 'password_credit'], 'required'],
            [['merchant_id', 'password_pay', 'password_credit'], 'trim'],
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
            'password_pay' => 'password',
            'password_credit' => 'password'
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
            'private_key' => 'password_pay',
            'field3' => 'password_credit'
        ];

        return $keys[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getRequiredParams()
    {
        return ['public_key', 'private_key', 'field3'];
    }
}