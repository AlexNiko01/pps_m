<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\PaymentSystemStatus;
use yii\db\Query;
use \backend\models\Node;

/**
 * PaymentSystemStatusSearch represents the model behind the search form of `backend\models\PaymentSystemStatus`.
 */
class PaymentSystemStatusSearch extends PaymentSystemStatus
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'payment_system_id', 'active', 'deleted'], 'integer'],
            [['name'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }


    public function search($params)
    {
        $nodeId = null;
        try {
            $nodeId = Node::getCurrentNode()->id;
        } catch (\yii\web\ForbiddenHttpException $e) {
            echo $e->getMessage();
        };
        $nodePsQuery = new Query;
        $nodePsQuery->select([
            'payment_system_id'
        ])
            ->from('user_payment_system')
            ->where(['node_id' => $nodeId]);
        $command = $nodePsQuery->createCommand(\Yii::$app->db2);
        $nodePsQuerySample = $command->queryAll();
        $nodesPsIds = [];
        if (!empty($nodePsQuerySample)) {
            foreach ($nodePsQuerySample as $psId) {
                if ($psId['payment_system_id'] ?? null) {
                    $nodesPsIds[] = $psId['payment_system_id'];
                }
            }
        }
        $query = PaymentSystemStatus::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'payment_system_id' => $this->payment_system_id,
            'active' => $this->active,
            'deleted' => $this->deleted,
        ]);
        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['in', 'id', $nodesPsIds]);

        return $dataProvider;
    }
}
