<?php

namespace common\models;

use pps\payment\Payment;
use yii\base\Model;
use yii\data\ActiveDataProvider;
require '../../vendor/pps/pps-payment/Payment.php';
/**
 * TransactionSearch represents the model behind the search form about `common\models\Transaction`.
 */
class TransactionSearch extends Transaction
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['brand_id', 'payment_system_id', 'merchant_transaction_id', 'buyer_id', 'updated_at'], 'integer'],
            [['way', 'currency', 'payment_method', 'external_id', 'requisites', 'status', 'comment', 'result_data', 'query_data', 'created_at'], 'safe'],
            [['amount', 'write_off', 'refund', 'receive'], 'number'],
            [['id', 'brands'], 'string'],
        ];
    }

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return \Yii::$app->db2;
    }

    /**
     * Creates data provider instance with search query applied
     * @param array $params
     * @param string $way
     * @return ActiveDataProvider
     */
    public function search($params, $way)
    {
        $query = Transaction::find();
        $query->joinWith('paymentSystem');
        // add conditions that should always apply here
        $sort = [
            'defaultOrder' => [
                'created_at' => SORT_DESC
            ]
        ];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => $sort,
        ]);

        $this->load($params);

        if ($this->status == -1) {
            $query->where(['IS', 'status', null]);
        } elseif (
            ($this->status != '') &&
            ($this->status >= 0)
        ) {
            $query->where(['status' => $this->status]);

        }

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'transaction.id' => $this->id,
            'way' => $way,
            'payment_system_id' => $this->payment_system_id,
            'amount' => $this->amount,
            'write_off' => $this->write_off,
            'refund' => $this->refund,
            'receive' => $this->receive,
            'merchant_transaction_id' => $this->merchant_transaction_id,
            'buyer_id' => $this->buyer_id,
//            'status' => $this->status,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'currency', $this->currency])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'external_id', $this->external_id])
            ->andFilterWhere(['like', 'requisites', $this->requisites])
            ->andFilterWhere(['in', 'status', [
                Payment::STATUS_TIMEOUT,
                Payment::STATUS_CANCEL,
                Payment::STATUS_ERROR,
                Payment::STATUS_MISPAID,
                Payment::STATUS_DSPEND,
                Payment::STATUS_VOIDED,
                Payment::STATUS_NETWORK_ERROR,
                Payment::STATUS_PENDING_ERROR]])
            ->andFilterWhere(['like', 'comment', $this->comment])
            ->andFilterWhere(['like', 'result_data', $this->result_data])
            ->andFilterWhere(['like', 'query_data', $this->query_data]);

        /* Фільтр по даті */
        if ($this->created_at) :
            $date_range = explode(' - ', $this->created_at);

            $query->andFilterWhere([
                'between',
                'transaction.created_at',
                strtotime($date_range[0] . ' 00:00:00'),
                strtotime($date_range[1] . ' 23:59:59')
            ]);
        endif;

        if ($this->brands) :
            $query->andFilterWhere([
                'IN',
                'brand_id',
                explode(',', $this->brands)
            ]);
        endif;

        return $dataProvider;
    }


}
