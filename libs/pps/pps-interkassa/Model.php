<?php

namespace pps\interkassa;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $user_id;
    public $api_key;
    public $shop_id;
    public $secret_key;


    public function attributeLabels(): array
    {
        return [
            'user_id' => 'User ID',
            'api_key' => 'Api key',
            'shop_id' => 'Shop ID',
            'secret_key' => 'Secret key',
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'secret_key', 'api_key', 'shop_id'], 'required'],
            [['user_id', 'secret_key', 'api_key', 'shop_id'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'user_id' => 'text',
            'api_key' => 'text',
            'shop_id' => 'text',
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
            'public_key' => 'user_id',
            'private_key' => 'api_key',
            'field3' => 'shop_id',
            'field4' => 'secret_key',
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