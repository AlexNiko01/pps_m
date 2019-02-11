<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "payments_systems_statuses".
 *
 * @property int $id
 * @property int $payment_system_id
 * @property string $name
 * @property int $active
 * @property int $deleted
 */
class PaymentSystemStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payments_systems_statuses';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payment_system_id', 'active', 'deleted'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'payment_system_id' => 'Payment System ID',
            'name' => 'Name',
            'active' => 'Active',
            'deleted' => 'Deleted',
        ];
    }
}
