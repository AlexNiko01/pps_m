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
        ->filterWhere(['not in', 'status', [0, 1, 2]])
        ->andFilterWhere(['>', 'updated_at', time() - 46000])
        ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
        ->all();

    foreach ($transactionsSample as $item) {
        echo('<b>Failed transaction id:</b> ' . $item->id
            . ' ;</br><b>Merchant transaction id:</b> ' . $item->merchant_transaction_id
            . ' ;</br><b>time:</b> ' . date('m-d-Y h:i:s', $item->updated_at)
            . ' ;</br><b>currency:</b> ' . $item->currency
            . ' ;</br><b>status:</b> ' . \pps\payment\Payment::getStatusDescription($item->status)
            . ' ;</br><b>payment system:</b> ' . $item->paymentSystem->name
            . '</br></br>');
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