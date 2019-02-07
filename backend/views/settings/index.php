<?php

use common\models\Transaction;
use yii\db\Query;
use yii\helpers\Html;
use kartik\grid\GridView;


/* @var $this yii\web\View */
/* @var $searchModel backend\models\SettingsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="settings-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Settings', ['create'], ['class' => 'btn btn-success']) ?>
    </p>


    <?php

    $transactionsSample = Transaction::find()
        ->filterWhere(['not in', 'status', [1, 2]])
        ->andFilterWhere(['>', 'updated_at', time() - 900])
        ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
        ->all();

    foreach ($transactionsSample as $item) {
        echo ('Failed transaction id: ' . $item->id
            . ' ; Merchant transaction id: ' . $item->merchant_transaction_id
            . ' ; time: ' . $item->updated_at
            . ' ; currency: ' . $item->currency
            . ' ; status: ' . \pps\payment\Payment::getStatusDescription($item->status)
            . ' ; payment system: ' . $item->paymentSystem->name
            . '</br>');
    };

    die();

    ?>


    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'attribute' => 'group',
                'filterType' => GridView::FILTER_POS_HEADER,
                'filter' => ['rocket_chat' => 'rocket_chat', 'telegram' => 'telegram'],
                'filterWidgetOptions' => [
                    'pluginOptions' => ['allowClear' => true],
                ],
                'filterInputOptions' => [
                    'placeholder' => 'group',
                    'class' => 'form-control'
                ],
            ],
            'key',
            'value',
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>