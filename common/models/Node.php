<?php

namespace common\models;

/**
 * This is the model class for table "node".
 * @property integer $id
 * @property integer $parent_id
 * @property integer $active
 * @property integer $type
 * @property string $name
 * @property string $domain
 * @property integer $verified
 * @property string $primary_email
 * @property string $note
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $is_api_responses_validated
 * @property Node $parent
 * @property Node[] $nodes
 */
class Node extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'node';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'active', 'type', 'verified', 'created_at', 'updated_at'], 'integer'],
            [['type', 'name'], 'required'],
            [['name', 'domain', 'callback_url'], 'string', 'max' => 255],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Node::className(), 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent ID',
            'active' => 'Active',
            'type' => 'Type',
            'name' => 'Name',
            'domain' => 'Domain',
            'verified' => 'Verified',
            'callback_url' => 'Callback Url',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Node::className(), ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNodes()
    {
        return $this->hasMany(Node::className(), ['parent_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return \common\models\query\NodeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \common\models\query\NodeQuery(get_called_class());
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->created_at = time();
        }

        $this->updated_at = time();

        return parent::beforeSave($insert);
    }
}
