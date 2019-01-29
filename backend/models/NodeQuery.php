<?php

namespace backend\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Node]].
 *
 * @see Node
 */
class NodeQuery extends ActiveQuery
{
    public function active()
    {
        $this->andWhere(['node.active'=>1]);
        return $this;
    }

    public function domain()
    {
        $this->andWhere(['node.type'=>Node::TYPE_DOMAIN]);
        return $this;
    }

    public function childBranch()
    {
        $this->andWhere(['node.id'=>array_keys(Node::getCurrentNode()->getChildrenList())]);
        return $this;
    }

    public function sorted()
    {
        $this->orderBy('node.sorter ASC');
        return $this;
    }

	/**
	* @inheritdoc
	* @return Node[]|array
	*/
	public function all($db = null)
	{
		return parent::all($db);
	}

	/**
	* @inheritdoc
	* @return Node|array|null
	*/
	public function one($db = null)
	{
		return parent::one($db);
	}
}