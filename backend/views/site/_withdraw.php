<?php

/* @var $this \yii\web\View */
/* @var $total array */
/* @var $currencies array */

/* @var $searchDataProvider object */

use backend\models\Node;
use webvimark\modules\UserManagement\components\GhostHtml;
use yii\grid\GridView;
use yii\helpers\{Html};
use kartik\export\ExportMenu;
use pps\payment\Payment;


$node = Node::getCurrentNode();

$this->title = "Withdraw {$node->name}";
$this->params['breadcrumbs'][] = $this->title;


$searchModel->load(\Yii::$app->request->post());

$is_super_admin = Yii::$app->user->isSuperAdmin;

?>

<div class="col-md-12 no-padding-left">
    <?= ExportMenu::widget([
        'dataProvider' => $searchDataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'merchant_transaction_id',
                'label' => 'Merch. tr. ID',
            ],
            [
                'attribute' => 'external_id',
                'label' => 'External ID',
                'visible' => $is_super_admin
            ],
            'currency',
            'amount',
            'write_off',
            'receive',
            'commission_payer',
            [
                'attribute' => 'payment_system_id',
                'label' => 'Payment system',
                'value' => function ($model, $key, $index) {
                    return $model->paymentSystem->name;
                }
            ],
            'payment_method',
            [
                'attribute' => 'merchant_transaction_id',
                'label' => 'Inside ID'
            ],
            'buyer_id',
            'status',
            'comment',
            [
                'attribute' => 'created_at',
                'value' => function ($model, $key, $index) {
                    return date('Y-m-d H:i:s', $model->created_at);
                }
            ],
            [
                'attribute' => 'updated_at',
                'value' => function ($model, $key, $index) {
                    return date('Y-m-d H:i:s', $model->updated_at);
                }
            ]
        ],
        'filename' => 'withdraw_transactions',
        'fontAwesome' => true,
        'target' => ExportMenu::TARGET_BLANK,
        'dropdownOptions' => [
            'label' => 'Export',
            'title' => 'Select format',
            'class' => 'btn btn-primary'
        ],
        'exportConfig' => [
            ExportMenu::FORMAT_PDF => false,
        ],
    ]);
    ?>
</div>

<?php
//echo $this->render('_search_form', [
//    'searchModel' => $searchModel,
//    'currency_list' => $currency_list,
//    'type' => 'withdraw',
//]) ?>

<style>
    .success {
        color: green
    }

    .error {
        color: brown;
    }

    .show-more {
        font-size: 12px;
        color: #00a7d0;
    }

    .show-more:hover {
        cursor: pointer;
    }
</style>

<!--?php Pjax::begin([
    'id' => 'transaction-deposit-pjax',
    'enablePushState' => false,
    'clientOptions' => [
        'method' => 'get'
    ]
]) ?-->
<?= GridView::widget([
    'id' => 'transaction-deposit-grid',
    'dataProvider' => $searchDataProvider,
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
        'commission_payer',
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

    ],
]); ?>
<!--?php Pjax::end() ?-->

<?php if (sizeof($total) > 0) : ?>
    <table class="table table-bordered" style="width: 50%; margin-top: 32px;">
        <thead>
        <tr>
            <th>Currency</th>
            <th>Total Merchant Refund</th>
            <th>Total Client Receive</th>
            <th>Total Tax</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($total as $currency => $item): ?>
            <tr>
                <td><?= $currency ?></td>
                <td><?= $total[$currency]['amount'] ?></td>
                <td><?= $total[$currency]['receive'] ?></td>
                <td><?= $total[$currency]['receive_tax'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

