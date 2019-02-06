<?php

namespace pps\qiwi;

use pps\payment\IModel;

/**
 * Class Model
 * @package pps\qiwi
 */
class Model extends \yii\base\Model implements IModel
{
    /** @var string */
    public $api_token;
    /** @var string */
    public $api_key;


    public function attributeLabels():array
    {
        return [
            'api_token' => 'API token',
            'api_key' => 'API key',
        ];
    }

    public function rules():array
    {
        return [
            [['api_token', 'api_key'], 'required'],
            [['api_token', 'api_key'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'api_token' => 'text',
            'api_key' => 'text',
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
            'public_key' => 'api_key',
            'private_key' => 'api_token',
        ];

        return $keys[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getRequiredParams(): array
    {
        return ['private_key', 'public_key'];
    }
}