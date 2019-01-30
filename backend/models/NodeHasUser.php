<?php

namespace backend\models;

use webvimark\components\BaseActiveRecord;
use Yii;

/**
 * @property int $id
 * @property int $node_id
 * @property int $user_id
 */
class NodeHasUser extends BaseActiveRecord
{
    protected $_enable_common_cache = true;
    protected $_timestamp_enabled = true;

    /**
     * @param int $nodeId
     * @param int $userId
     */
    public static function assignUser($nodeId, $userId)
    {
        $model = new NodeHasUser();
        $model->node_id = $nodeId;
        $model->user_id = $userId;
        $model->save(false);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'node_id'=>Yii::t('admin', 'Node'),
            'user_id'=>Yii::t('admin', 'User'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['user_id', 'node_id'], 'integer'],
            [['user_id', 'node_id'], 'unique', 'targetAttribute' => ['user_id', 'node_id']],
            [['user_id', 'node_id'], 'required', 'on'=>'frontAssign'],
        ];
    }
}