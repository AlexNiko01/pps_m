<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "auth_log".
 *
 * @property int $id
 * @property string $ip
 * @property string $user_agent
 * @property int $attempts
 * @property int $block
 * @property int $unblocking_time
 * @property int $blocking_quantity
 */
class AuthLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['attempts', 'block', 'blocking_quantity','unblocking_time'], 'integer'],
            [['ip', 'user_agent'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'user_agent' => 'User Agent',
            'attempts' => 'Attempts',
            'block' => 'Block',
            'unblocking_time' => 'Unblocking Time',
            'blocking_quantity' => 'Blocking Quantity',
        ];
    }
}
