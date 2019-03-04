<?php

use kartik\grid\BooleanColumn;
use kartik\grid\DataColumn;
use kartik\grid\GridView;
use yii\widgets\Pjax;

Pjax::begin([
    'id' => 'status_projects-pjax',
    'enablePushState' => false,
    'clientOptions' => [
        'method' => 'post'
    ]
]);

echo GridView::widget([
    'dataProvider' => $dataProviderProjects,
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
            'class' => BooleanColumn::className(),
            'attribute' => 'active',
        ]
    ],
]);
Pjax::end();