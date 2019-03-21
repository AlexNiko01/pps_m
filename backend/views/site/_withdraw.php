<?php

/* @var $this \yii\web\View */
/* @var $total array */
/* @var $currencies array */

/* @var $searchDataProvider object */

use backend\models\Node;
use yii\grid\GridView;
use yii\helpers\{Html};
use pps\payment\Payment;
use yii\widgets\Pjax;

$node = Node::getCurrentNode();
$isSuperAdmin = Yii::$app->user->isSuperAdmin;
?>

<?php Pjax::begin([
    'id' => 'transaction-withdraw-pjax',
    'enablePushState' => false,
    'clientOptions' => [
        'method' => 'post'
    ]
]) ?>
<?= GridView::widget([
    'id' => 'transaction-withdraw-grid',
    'dataProvider' => $dataProviderWithdraw,
    'showFooter' => false,
    'summary' => false,
    'pager' => [
        'options' => [
            'class' => 'pagination pagination-sm'
        ],
        'hideOnSinglePage' => true,
        'lastPageLabel' => '>>',
        'firstPageLabel' => '<<',
    ],
    'tableOptions' => [
        'class' => 'table table-striped table-bordered table-hover'
    ],
    'columns' => [
        [
            'class' => 'yii\grid\SerialColumn',
            'options' => [
                'style' => 'width:10px'
            ]
        ],
        [
            'attribute' => 'id',
            'label' => 'Transaction ID',
            'format' => 'raw',
            'value' => function ($model) {
                return '<span class="text-overflow">' . Html::encode($model->id) . '</span>';
            },
            'headerOptions' => [
                'class' => 'tid-column-header'
            ],
            'contentOptions' => [
                'class' => 'tid-column'
            ]
        ],
        [
            'attribute' => 'merchant_transaction_id',
            'label' => 'Merch. trans. ID',
            'format' => 'raw',
            'value' => function ($model) {
                return '<span class="text-overflow">' . ($model->merchant_transaction_id ? Html::encode($model->merchant_transaction_id) : '') . '</span>';
            },
            'headerOptions' => [
                'class' => 'mtid-column-header'
            ],
            'contentOptions' => [
                'class' => 'mtid-column'
            ],
        ],

        [
            'attribute' => 'payment_system_id',
            'label' => 'Payment System',
            'value' => function ($model) {
                return $model->paymentSystem->name ?? '';
            },
            'headerOptions' => [
                'class' => 'payment_system_id-column-header'
            ],
            'contentOptions' => [
                'class' => 'payment_system_id-column'
            ],
        ],
        [
            'attribute' => 'payment_method',
            'value' => function ($model) {
                return $model->payment_method ?? '';
            },
            'headerOptions' => [
                'class' => 'payment_method-column-header'
            ],
            'contentOptions' => [
                'class' => 'payment_method-column'
            ],
        ],
        [
            'attribute' => 'status',
            'format' => 'raw',
            'value' => function ($model) {
                if ($model->status == Payment::STATUS_SUCCESS) {
                    return '<span class="success">' . Payment::getStatusDescription($model->status) . '</span>';
                } else if ($model->status == Payment::STATUS_CREATED) {
                    return '<span style="color: #777;">' . Payment::getStatusDescription($model->status) . '</span>';
                } else if ($model->status == Payment::STATUS_ERROR) {
                    return '<span class="error">' . Payment::getStatusDescription($model->status) . '</span>';
                } else if ($model->status == Payment::STATUS_CANCEL) {
                    return '<span style="color: indianred;">' . Payment::getStatusDescription($model->status) . '</span>';
                } else {
                    return isset($model->status) ? Payment::getStatusDescription($model->status) : '-';
                }
            },
            'headerOptions' => [
                'class' => 'status-column-header'
            ],
            'contentOptions' => [
                'class' => 'status-column'
            ],
        ],
        [
            'attribute' => 'updated_at',
            'value' => function ($model) {
                return date("M d Y H:i:s", $model->updated_at);
            }
        ],
        'way'

    ],
]); ?>
<?php Pjax::end(); ?>

