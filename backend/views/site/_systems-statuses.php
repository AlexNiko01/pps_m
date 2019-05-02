<?php

use kartik\grid\BooleanColumn;
use kartik\grid\DataColumn;
use kartik\grid\GridView;
use yii\widgets\Pjax;



Pjax::begin([
    'id' => 'status-pjax',
    'enablePushState' => false,
    'clientOptions' => [
        'method' => 'post'
    ]
]);

echo GridView::widget([
    'dataProvider' => $dataProviderSystems,
    'id' => 'status-grid',
    'summary' => false,
    'columns' => [
        [
            'class' => 'yii\grid\SerialColumn',
            'headerOptions' => ['style' => 'width:5%']
        ],
        [
            'class' => DataColumn::className(),
            'attribute' => 'name',
            'contentOptions' =>
                ['style' =>
                    ['text-align' => 'left']
                ]
        ],
        [
            'class' => DataColumn::className(),
            'attribute' => 'active',
            'value' => function ($model) {
                $val = 'disable';
                switch (@$model->active) {
                    case 1;
                        $val = 'enable';
                        break;
                    case 2;
                        $val = 'not enough data for determine payment system status';
                        break;
                };
                return $val;
            },
        ]
    ],
]);
Pjax::end();