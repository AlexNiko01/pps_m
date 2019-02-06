<?php

namespace pps\ecommpay;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $site_id;
    public $salt;


    public function attributeLabels(): array
    {
        return [
            'site_id' => 'Site key',
            'salt' => 'Salt',
        ];
    }

    public function rules(): array
    {
        return [
            [['site_id', 'salt'], 'required'],
            [['site_id', 'salt'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'salt' => 'text',
            'site_id' => 'text',
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
            'public_key' => 'site_id',
            'private_key' => 'salt',
        ];

        return $keys[$key] ?? null;
    }
}