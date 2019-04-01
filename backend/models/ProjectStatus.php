<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "projects_statuses".
 *
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property int $active
 * @property int $node_id
 * @property int $deleted
 */
class ProjectStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'projects_statuses';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['active', 'node_id', 'deleted'], 'integer'],
            [['name', 'domain'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'domain' => 'Domain',
            'active' => 'Active',
            'node_id' => 'Node ID',
            'deleted' => 'Deleted',
        ];
    }
}
