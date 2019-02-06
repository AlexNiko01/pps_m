<?php

namespace pps\dengionline;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    public $project_id;
    public $secret_key;

    public function attributeLabels():array
    {
        return [
            'project_id' => 'Project ID',
            'secret_key' => 'Secret key',
        ];
    }

    public function rules():array
    {
        return [
            [['secret_key', 'project_id'], 'required'],
            [['secret_key', 'project_id'], 'trim'],
        ];
    }

    /**
     * Get public attributes and types
     * @return array
     */
    public function getFields():array
    {
        return [
            'project_id' => 'text',
            'secret_key' => 'text',
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
            'public_key' => 'project_id',
            'private_key' => 'secret_key',
        ];

        return $keys[$key] ?? null;
    }
}