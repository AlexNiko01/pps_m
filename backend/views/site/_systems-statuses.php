<?php

use kartik\grid\BooleanColumn;
use kartik\grid\DataColumn;
use kartik\grid\GridView;

echo GridView::widget([
    'dataProvider' => $dataProviderSystems,
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
            'trueLabel' => 'Yes',
            'falseLabel' => 'No'
        ]
    ],
]);