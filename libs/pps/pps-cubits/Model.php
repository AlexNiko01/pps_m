<?php

namespace pps\cubits;

use pps\payment\IModel;

/**
 * Class Model
 * @package pps\cubits
 */
class Model extends \yii\base\Model implements IModel
{
    public $cubits_key;
    public $secret_key;


    public function attributeLabels(): array
    {
        return [
            'cubits_key' => 'Cubits key',
            'secret_key' => 'Secret key',
        ];
    }

    public function rules(): array
    {
        return [
            [['cubits_key', 'secret_key'], 'required'],
            [['cubits_key', 'secret_key'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields(): array
    {
        return [
            'cubits_key' => 'text',
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
            'public_key' => 'cubits_key',
            'private_key' => 'secret_key',
        ];

        return $keys[$key] ?? null;
    }
}