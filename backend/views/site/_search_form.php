<?php
/**
 * @var $searchModel
 * @var $params
 * @var $type
 */

use backend\models\PaymentSystem;
use kartik\daterange\DateRangePicker;
use pps\payment\Payment;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$payment_systems = ArrayHelper::map(PaymentSystem::find()->all(), 'id', 'name');
$payment_statuses = Payment::getStatuses();

asort($payment_systems);
asort($payment_statuses);
//asort($currency_list);

$params = [
    'prompt' => Yii::t('admin', 'All'),
    'onchange' => "$('#transaction-search').submit()"
];

?>

<?php $form = ActiveForm::begin([
    'method' => 'get',
    'validateOnBlur' => false,
    'id' => 'transaction-search'
]); ?>

    <div class="transaction-search">
        <?= $form->field($searchModel, 'id', [
            'options' => [
                'class' => 'form-group col-md-2 no-padding-left'
            ]
        ]) ?>

        <?= $form->field($searchModel, 'merchant_transaction_id', [
            'options' => [
                'class' => 'form-group col-md-2 no-padding-left'
            ]
        ]) ?>
<!---->
<!--        --><?//= $form->field($searchModel, 'currency', [
//            'options' => [
//                'class' => 'form-group col-md-1 no-padding-left'
//            ]
//        ])->dropDownList($currency_list, $params) ?>

        <?= $form->field($searchModel, 'payment_system_id', [
            'options' => [
                'class' => 'form-group col-md-1 no-padding-left'
            ]
        ])->dropDownList($payment_systems, $params) ?>

        <?= $form->field($searchModel, 'status', [
            'options' => [
                'class' => 'form-group col-md-1 no-padding-left'
            ]
        ])->dropDownList($payment_statuses, $params) ?>

        <?= $form
            ->field($searchModel, 'created_at', [
                'options' => [
                    'class' => 'form-group col-md-2 no-padding-left'
                ]
            ])
            ->widget(DateRangePicker::classname(), [
                'id' => 'transaction-search-form-created_at',
                'convertFormat' => true,
                'pluginOptions' => [
                    'format' => 'd.m.Y',
                    'separator' => ' - ',
                    'locale' => [
                        'format' => 'd.m.Y'
                    ]
                ],
                'options' => [
                    'placeholder' => 'Selection date range',
                    'class' => 'form-control',
                    'onchange' => "$('#transaction-search').submit()"
                ],
            ]);
        ?>

        <div class="form-group col-md-2 no-padding-left btn-group" style="margin-top: 25px;">
            <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Reset', $type, [
                'class' => 'btn btn-default',
                'id' => 'transaction-search-form-cancel_btn',
            ]) ?>
        </div>
    </div>

<?php ActiveForm::end(); ?>