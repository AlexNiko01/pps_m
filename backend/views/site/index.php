<?php

/**
 * @var int $countOfClients
 * @var int $txsToday
 * @var int $all
 * @var int $finishedPercent
 * @var int $countOfBrands
 * @var int $countOfPaymentSystems
 * @var array $countOfStatuses
 * @var array $countOfDepositStatuses
 * @var array $countOfWithdrawStatuses
 * @var array $pendingTransactions
 * @var int $countOfWithdraw
 * @var int $countOfDeposit
 * @var int $percentOfFinalWithdrawStatuses
 * @var int $percentOfFinalDepositStatuses
 * @var int $days
 * @var int $stepDeposit
 * @var int $stepWithdraw
 * @var array $txsByMerchant
 * @var array $chartLabels
 * @var array $countOfDepositTxsByMinutes
 * @var array $countOfWithdrawTxsByMinutes
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard';


?>
<?= Html::script('', ['src' => Url::to(['/js/chart.min.js'])]) ?>

    <style>

    </style>
    <div class="row">
        <div class="col-lg-3 col-xs-12">
            <h3>Payment systems statuses</h3>
            <?php echo $this->render('_systems-statuses', [
                'searchModelSystems' => $searchModelSystems,
                'dataProviderSystems' => $dataProviderSystems
            ]); ?>
        </div>
        <div class="col-lg-6 col-xs-12">
            <h3>Projects statuses</h3>
            <?php echo $this->render('_projects-statuses', [
                'searchModelProjects' => $searchModelProjects,
                'dataProviderProjects' => $dataProviderProjects
            ]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 col-xs-12">
            <h3>Deposit transactions</h3>
            <?php echo $this->render('_deposit', [
                'searchModelDeposit' => $searchModelDeposit,
                'dataProviderDeposit' => $dataProviderDeposit
            ]); ?>

        </div>
        <div class="col-lg-6 col-xs-12">
            <h3>Withdraw transactions</h3>
            <?php echo $this->render('_withdraw', [
                'searchModelWithdraw' => $searchModelWithdraw,
                'dataProviderWithdraw' => $dataProviderWithdraw,
            ]); ?>
        </div>
    </div>
<?php echo $this->render('_graphs', [
    'days' => $days,
    'stepDeposit' => $stepDeposit,
    'stepWithdraw' => $stepWithdraw,
    'countOfDepositTxsByMinutes' => $countOfDepositTxsByMinutes,
    'countOfWithdrawTxsByMinutes' => $countOfWithdrawTxsByMinutes,
    'countOfStatuses' => $countOfStatuses,
    'countOfDepositStatuses' => $countOfDepositStatuses,
    'countOfWithdrawStatuses' => $countOfWithdrawStatuses,
]); ?>
<!--<div class="row">-->
<!--    <div class="col-lg-6 col-xs-12">-->
<!--        <div class="transactions">-->
<!--            <h4 class="text-center">Suspended transactions (count: --><?//= count($pendingTransactions) ?><!--)</h4>-->
<!--            --><?php //if(!empty($pendingTransactions)): ?>
<!--                <table class="table table-bordered">-->
<!--                    <thead>-->
<!--                    <tr>-->
<!--                        <th>ID</th>-->
<!--                        <th>Way</th>-->
<!--                        <th>PS</th>-->
<!--                        <th>Buyer ID</th>-->
<!--                        <th>Status</th>-->
<!--                        <th>Created At</th>-->
<!--                    </tr>-->
<!--                    </thead>-->
<!--                    <tbody>-->
<!--                    --><?php //foreach ($pendingTransactions as $tx): ?>
<!--                        <tr>-->
<!--                            <td>--><?//= Html::a($tx['id'], ['transaction/view', 'id' => $tx['id']], ['class' => 'btn btn-xs btn-link']) ?><!--</td>-->
<!--                            <td>--><?//= $tx['way'] ?><!--</td>-->
<!--                            <td>--><?//= $tx['payment_system'] ?><!--</td>-->
<!--                            <td>--><?//= $tx['buyer_id'] ?><!--</td>-->
<!--                            <td>--><?//= $statuses[$tx['status']] ?><!--</td>-->
<!--                            <td>--><?//= date('Y-m-d H:s:i', $tx['created_at']) ?><!--</td>-->
<!--                        </tr>-->
<!--                    --><?php //endforeach; ?>
<!--                    </tbody>-->
<!--                </table>-->
<!--            --><?php //else: ?>
<!--                <p>Suspended transactions not found!</p>-->
<!--            --><?php //endif;?>
<!--        </div>-->
<!--    </div>-->
<!--    <div class="col-lg-6 col-xs-12">-->
<!--        <div class="merchants">-->
<!--            <h4 class="text-center">Merchants (count: --><?//= count($txsByMerchant) ?><!--)</h4>-->
<!--            --><?php //if (!empty($txsByMerchant)):?>
<!--                <table class="table table-bordered">-->
<!--                    <thead>-->
<!--                    <tr>-->
<!--                        <th>ID</th>-->
<!--                        <th>Name</th>-->
<!--                        <th>Count Trxs</th>-->
<!--                        <th>Created At</th>-->
<!--                    </tr>-->
<!--                    </thead>-->
<!--                    <tbody>-->
<!--                    --><?php //foreach ($txsByMerchant as $merchant): ?>
<!--                        <tr>-->
<!--                            <td>--><?//= $merchant['brand_id'] ?><!--</td>-->
<!--                            <td>--><?//= $merchant['name'] ?><!--</td>-->
<!--                            <td>--><?//= $merchant['count'] ?><!--</td>-->
<!--                            <td>--><?//= date('Y-m-d H:s:i', $merchant['created_at']) ?><!--</td>-->
<!--                        </tr>-->
<!--                    --><?php //endforeach; ?>
<!--                    </tbody>-->
<!--                </table>-->
<!--            --><?php //else: ?>
<!--                <p>Merchants not found!</p>-->
<!--            --><?php //endif;?>
<!--        </div>-->
<!--    </div>-->
<!--</div>-->
