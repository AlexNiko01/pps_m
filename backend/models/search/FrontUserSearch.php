<?php

namespace backend\models\search;

use backend\models\{
    Node, NodeHasUser
};
use webvimark\modules\UserManagement\models\search\UserSearch;
use Yii;
use yii\data\ActiveDataProvider;

class FrontUserSearch extends UserSearch
{
    /**
     * Used in gridview
     *
     * @var int
     */
    public $assignments;

    /**
     * @return null|string
     */
    public function getAssignments()
    {
        $out = [];
        foreach ($this->nodes as $node) {
            $out[$node->id] = sprintf('[#%s] %s', $node->id, $node->name);
        }

        return $out ? implode('<br>', $out) : null;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['status', 'assignments'], 'integer'],
            [['username', 'gridRoleSearch'], 'string'],
        ];
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchOnSelectedNode($params)
    {
        $query = static::find();

        $query->with('roles');
        $query->joinWith(['nodeHasUsers']);

        $query->andWhere(['superadmin' => 0])
            ->andWhere(['<>', 'user.id', Yii::$app->user->id])
            ->andWhere([
                'node_has_user.node_id' => Node::getCurrentNode()->id,
            ]);


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Yii::$app->request->cookies->getValue('_grid_page_size', 20),
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        if ($this->gridRoleSearch) {
            $query->andFilterWhere([
                'auth_item.name' => $this->gridRoleSearch,
            ]);
        }

        $query->andFilterWhere([
            'user.status' => $this->status,
            'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['like', 'user.username', $this->username]);

        return $dataProvider;
    }

    /**
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function searchOnBranch($params)
    {
        $query = static::find();

        $query->with('roles');
        $query->joinWith(['nodes']);

        $query->andWhere(['superadmin' => 0])
            ->andWhere(['<>', 'user.id', Yii::$app->user->id])
            ->andWhere(['in', 'node.id', array_keys(Node::getCurrentNode()->getChildrenList(false))])
            ->andWhere(['<>', 'node.id', Node::getCurrentNode()->id]);


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Yii::$app->request->cookies->getValue('_grid_page_size', 20),
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'user.status' => $this->status,
            'auth_item.name' => $this->gridRoleSearch,
            'node.id' => $this->assignments,
        ]);

        $query->andFilterWhere(['like', 'user.username', $this->username]);

        return $dataProvider;
    }

    public function getNodeHasUsers()
    {
        return $this->hasMany(NodeHasUser::className(), ['user_id' => 'id']);
    }

    public function getNodes()
    {
        return $this->hasMany(Node::className(), ['id' => 'node_id'])->via('nodeHasUsers');
    }
}