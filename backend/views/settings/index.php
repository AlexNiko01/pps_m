<?php

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
    $transactionsSample = common\models\Transaction::find()
        ->filterWhere(['not in', 'status', [1, 2]])
        ->andFilterWhere(['not', ['id' => null]])
        ->andFilterWhere(['>', 'updated_at', time() - 36000])
        ->select(['updated_at', 'id', 'merchant_transaction_id', 'status', 'currency', 'payment_system_id'])
        ->asArray()
        ->all();
    foreach ($transactionsSample as $item) {
        var_dump($item);
    }
    die(); ?>
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