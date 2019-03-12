<?php

namespace backend\models;

use common\components\exception\SettingsException;

/**
 * This is the model class for table "settings".
 *
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string $value
 */
class Settings extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group', 'key', 'value'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group' => 'Group',
            'key' => 'Key',
            'value' => 'Value',
        ];
    }

    /**
     * @param $key
     * @return mixed|null
     * @throws SettingsException
     */
    public static function getValue($key)
    {
        $sample = Settings::find()->where(['key' => $key])->select('value')->asArray()->one();
        $val = $sample ? $sample['value'] : null;

        if ($val === null) {
            throw new SettingsException('value ' . $key . ' is not exist in settings');
        }
        return $val;
    }
}
