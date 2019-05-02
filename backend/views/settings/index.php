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


    <p>
        <?= Html::a('Create Settings', ['create'], ['class' => 'btn btn-success']) ?>
    </p>


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