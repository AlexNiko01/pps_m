<?php

namespace backend\models\search;

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

    /**
     * @param $params
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function search($params)
    {
        $nodeId = null;
        try {
            $nodeId = Node::getCurrentNode()->id;
        } catch (\yii\web\ForbiddenHttpException $e) {
            echo $e->getMessage();
        };

        $children = Node::find()
            ->andWhere([
                'parent_id' => $nodeId,
            ])
            ->asArray()
            ->all();

        $nodesPsIds = [];
        $nodePsQuerySample = [];
        if (!empty($children)) {
            foreach ($children as $child) {
                $nodePsQuerySample += $this->getPsSample($child['id']);
            }
        } else {
            $nodePsQuerySample = $this->getPsSample($nodeId);
        }

        if (!empty($nodePsQuerySample)) {
            foreach ($nodePsQuerySample as $psId) {
                if ($psId['payment_system_id'] ?? null) {
                    $nodesPsIds[] = $psId['payment_system_id'];
                }
            }
        }
//        echo '<pre>';
//        var_dump(array_unique($nodesPsIds));
//        echo '</pre>';
//        die();
        $query = PaymentSystemStatus::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'payment_system_id' => $this->payment_system_id,
            'active' => $this->active,
            'deleted' => $this->deleted,
        ]);
        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['in', 'payment_system_id', $nodesPsIds]);

        return $dataProvider;
    }

    /**
     * @param $id
     * @return array
     * @throws \yii\db\Exception
     */
    private function getPsSample($id): array
    {
        $nodePsQuery = new Query;
        $nodePsQuery->select([
            'user_payment_system.payment_system_id', 'node.id', 'node.type'])
            ->from('user_payment_system')
            ->leftJoin('node', 'node.id = user_payment_system.node_id')
            ->where(['user_payment_system.node_id' => $id])
            ->orWhere(['node.parent_id' => $id]);
        $command = $nodePsQuery->createCommand(\Yii::$app->db2);
        $nodePsQuerySample = $command->queryAll();

        return $nodePsQuerySample;
    }
}
