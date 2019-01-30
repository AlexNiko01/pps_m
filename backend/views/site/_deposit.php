<?php

/* @var $total array */
/* @var $currencies array */
/* @var $searchDataProvider object */

use backend\models\Node;
use yii\grid\GridView;
use yii\helpers\{
    ArrayHelper, Html, Url
};
use yii\widgets\{
    ActiveForm, Pjax
};
use kartik\export\ExportMenu;
use pps\payment\Payment;
use common\classes\CurrencyList;
use \webvimark\modules\UserManagement\components\GhostHtml;


$node = Node::getCurrentNode();

$this->title = "Deposit {$node->name}";
$this->params['breadcrumbs'][] = $this->title;

$currency_list = CurrencyList::getArrayCurrencies($currencies);

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

<?= $this->render('_search_form', [
    'searchModel' => $searchModel,
    'currency_list' => $currency_list,
    'type' => 'deposit',
])?>

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
    pre {
        text-align: left;
    }

    body {
        overflow: auto;
    }

    .btn-marg {
        margin-left: 2px;
        margin-right: 2px;
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
                'class' => 'tid-column'
            ],
        ],
        [
            'attribute' => 'currency',
            'value' => function ($model) {
                return $model->currency ?? '';
            },
            'headerOptions' => [
                'class' => 'currency-column-header'
            ],
            'contentOptions' => [
                'class' => 'currency-column'
            ],
        ],
        [
            'attribute' => 'amount',
            'value' => function ($model) {
                return $model->amount ?? '';
            },
            'headerOptions' => [
                'class' => 'amount-column-header'
            ],
            'contentOptions' => [
                'class' => 'amount-column'
            ],
        ],
        [
            'attribute' => 'write_off',
            'value' => function ($model) {
                return $model->write_off ?? '';
            },
            'headerOptions' => [
                'class' => 'write_off-column-header'
            ],
            'contentOptions' => [
                'class' => 'write_off-column'
            ],
        ],
        [
            'attribute' => 'refund',
            'value' => function ($model) {
                return $model->refund ?? '';
            },
            'headerOptions' => [
                'class' => 'refund-column-header'
            ],
            'contentOptions' => [
                'class' => 'refund-column'
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
        //'buyer_id',
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
        /*[
            'attribute' => 'requisites',
            'format' => 'raw',
            'value' => function ($model) {
                $data = '';

                if ($model->requisites != '[]' && $model->requisites != '') {
                    if ($jsons = json_decode($model->requisites, true)) {
                        if (count($jsons) > 1) {
                            $firstKey = array_first(array_keys($jsons));
                            $data .= "<b>" . Html::encode($firstKey) . "</b>: " . Html::encode($jsons[$firstKey]) . "<br />";
                            $data .= '<div class="requisites r-' . $model->id . '" style="display: none;">';
                            foreach ($jsons AS $key => $value) {
                                if ($key == $firstKey) continue;
                                $data .= "<b>" . Html::encode($key) . "</b>: " . Html::encode($value) . "<br />";
                            }
                            $data .= '</div>';
                            $data .= '<span class="show-more" onclick="$(\'.requisites.r-' . $model->id . '\').toggle()">Show more</span>';

                        } else {
                            foreach ($jsons AS $key => $value) {
                                $data .= "<b>" . Html::encode($key) . "</b>: " . Html::encode($value) . "<br />";
                            }
                        }
                    }
                }

                return $data;
            },
            'headerOptions' => [
                'class' => 'requisites-column-header'
            ],
            'contentOptions' => [
                'class' => 'requisites-column',
                'style' => 'text-align: left;'
            ],
        ],*/
        [
            'attribute' => 'created_at',
            'value' => function ($model) {
                return (!empty($model->created_at) ? date("d.m.Y H:i:s", $model->created_at) : '');
            },
            'headerOptions' => [
                'class' => 'created-date-column-header'
            ],
            'contentOptions' => [
                'class' => 'created-date-column'
            ],
        ],
        [
            'attribute' => 'updated_at',
            'value' => function ($model) {
                return (!empty($model->updated_at) ? date("d.m.Y H:i:s", $model->updated_at) : '');
            },
            'headerOptions' => [
                'class' => 'created-date-column-header'
            ],
            'contentOptions' => [
                'class' => 'created-date-column'
            ],
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view-transaction}{change-sum}',
            'buttons' => [
                'view-transaction' => function ($url, $model) {
                    if (!empty($model->id)) {
                        return GhostHtml::a('View', [
                            'view',
                            'id' => $model->id
                        ], [
                                'class' => 'btn btn-info btn-xs btn-marg pull-left'
                            ]
                        );
                    } else {
                        return '';
                    }
                },
//                'change-sum' => function($url, $model) 
//                {
//                    if (floatval($model->refund) > floatval($model->amount)) :
//                        return Html::a('change sum', '#', [
//                                'class' => 'btn btn-info btn-xs btn-marg pull-left',
//                                'id' => 'cs_' . $model->id,
//                                'onclick' => "change_sum('{$model->id}', 'cs_');return false;"
//                            ]
//                        );
//                    endif;
//                },
            ]
        ],
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
