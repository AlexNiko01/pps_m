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
        <?php echo $this->render('_systems-statuses', [
            'searchModelSystems' => $searchModelSystems,
            'dataProviderSystems' => $dataProviderSystems
        ]); ?>
    </div>
    <div class="col-lg-6 col-xs-12">
        <?php echo $this->render('_projects-statuses', [
            'searchModelProjects' => $searchModelProjects,
            'dataProviderProjects' => $dataProviderProjects
        ]); ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-xs-12">
        <h3> Deposit transactions</h3>
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