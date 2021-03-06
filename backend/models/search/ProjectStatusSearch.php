<?php

namespace backend\models\search;

use backend\models\Node;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\ProjectStatus;

/**
 * ProjectStatusSearch represents the model behind the search form of `backend\models\ProjectStatus`.
 */
class ProjectStatusSearch extends ProjectStatus
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'active', 'node_id', 'deleted'], 'integer'],
            [['name', 'domain'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $nodeId = null;
        try {
            $nodeId = Node::getCurrentNode()->id;
        } catch (\yii\web\ForbiddenHttpException $e) {
            echo $e->getMessage();
        };
        $query = ProjectStatus::find();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }
        if ($nodeId === 1) {
            $nodeId = $this->node_id;
        }
        $query->andFilterWhere([
            'id' => $this->id,
            'active' => $this->active,
            'node_id' => $nodeId,
            'deleted' => $this->deleted,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'domain', $this->domain]);

        return $dataProvider;
    }
}
