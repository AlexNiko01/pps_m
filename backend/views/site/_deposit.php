<?php

/* @var $total array */
/* @var $currencies array */

/* @var $searchDataProvider object */

use backend\models\Node;
use pps\payment\Payment;
use yii\grid\GridView;
use yii\helpers\{
    ArrayHelper, Html, Url
};
use yii\widgets\{
    ActiveForm, Pjax
};
use kartik\export\ExportMenu;

//use common\classes\CurrencyList;
use \webvimark\modules\UserManagement\components\GhostHtml;


$node = Node::getCurrentNode();

$this->title = "Deposit {$node->name}";
$this->params['breadcrumbs'][] = $this->title;

//$currency_list = CurrencyList::getArrayCurrencies($currencies);

$searchModelDeposit->load(\Yii::$app->request->post());

$is_super_admin = Yii::$app->user->isSuperAdmin;

?>

<div class="col-md-12 no-padding-left">
    <?= ExportMenu::widget([
        'dataProvider' => $dataProviderDeposit,
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
            'refund',
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
        'filename' => 'deposit_transactions',
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

<? //= $this->render('_search_form', [
//    'searchModel' => $searchModel,
//    'currency_list' => $currency_list,
//    'type' => 'deposit',
//]) ?>


<!--?php Pjax::begin([
    'id' => 'transaction-deposit-pjax',
    'enablePushState' => false,
    'clientOptions' => [
        'method' => 'get'
    ]
]) ?-->

<?= GridView::widget([
    'id' => 'transaction-deposit-grid',
    'dataProvider' => $dataProviderDeposit,
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
                'class' => 'tid-column'
            ],
        ],
        [
            'attribute' => 'payment_system_id',
            'label' => 'Payment System',
            'value' => function ($model) {
//                var_dump($model);
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
            'attribute' => 'external_id',
            'label' => 'External ID',
            'format' => 'raw',
            'value' => function ($model) {
                return $model->id ? '<span class="text-overflow">' . Html::encode($model->external_id) . '</span>' : '';
            },
            'headerOptions' => [
                'class' => 'external_id-column-header'
            ],
            'contentOptions' => [
                'class' => 'external_id-column'
            ],
            'visible' => $is_super_admin
        ],
        [
            'attribute' => 'status',
            'format' => 'raw',
//            'value' => function ($model) {
//                if ($model->status == Payment::STATUS_SUCCESS) {
//                    return '<span class="success">' . Payment::getStatusDescription($model->status) . '</span>';
//                } else if ($model->status == Payment::STATUS_CREATED) {
//                    return '<span style="color: #777;">' . Payment::getStatusDescription($model->status) . '</span>';
//                } else if ($model->status == Payment::STATUS_ERROR) {
//                    return '<span class="error">' . Payment::getStatusDescription($model->status) . '</span>';
//                } else if ($model->status == Payment::STATUS_CANCEL) {
//                    return '<span style="color: indianred;">' . Payment::getStatusDescription($model->status) . '</span>';
//                } else {
//                    return isset($model->status) ? Payment::getStatusDescription($model->status) : '-';
//                }
//            },
            'headerOptions' => [
                'class' => 'status-column-header'
            ],
            'contentOptions' => [
                'class' => 'status-column'
            ],
        ],
        'way'
    ],
]); ?>

<!--?php Pjax::end() ?-->

<?php if (sizeof($total) > 0) : ?>
    <table class="table table-bordered" style="width: 50%; margin-top: 32px;">
        <thead>
        <tr>
            <th>Currency</th>
            <th>Total Client Refund</th>
            <th>Total Merchant Receive</th>
            <th>Total Tax</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($total as $currency => $item): ?>
            <tr>
                <td><?= $currency ?></td>
                <td><?= $total[$currency]['amount'] ?></td>
                <td><?= $total[$currency]['refund'] ?></td>
                <td><?= $total[$currency]['refund_tax'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Modal -->
<div class="modal" id="show-notify-info" tabindex="-1" role="dialog" aria-labelledby="notify-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        onclick="$('#show-notify-info').modal('hide')"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Notify info</h4>
            </div>
            <div class="modal-body">
                ...
            </div>
        </div>
    </div>
</div>

<script>
    function notify(tr_id, br_id) {
        var data = {
            tr_id: tr_id,
            br_id: br_id
        };

        var header = $('.modal-header');
        header.removeClass('alert-error');
        header.removeClass('alert-success');

        $.get('<?= Url::to(['transaction/notify'])?>', data, function (res) {
            if (res.message) {
                $('#show-notify-info .modal-body').html(res.message);

                if (res.status === 'error') {
                    header.addClass('alert-error');
                }

                if (res.status === 'success') {
                    header.addClass('alert-success');
                }
            }

            $('#show-notify-info').modal('show');
        });
    }
    <?php /*
    function change_sum(id, prefix) {
        var data = {
            id: id
        };

        $.get('<?= Url::to(['transaction/change-sum'])?>', data, function(res) { });

        $('#' + prefix + id).remove();
    }
 */ ?>
</script>
