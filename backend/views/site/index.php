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
<div class="row">
    <div class="col-md-12">
        <div class="statuses">
            <h4 class="text-center" style="padding: 4px;">Count of deposit transactions for <?= $days ?> days</h4>
            <?php if (!empty($countOfDepositTxsByMinutes)): ?>
                <canvas id="txs-by-minutes-deposit" width="900" height="260"></canvas>
            <?php else: ?>
                <h5 class="text-center" style="padding: 20px;">Deposit transactions not found</h5>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if (!empty($countOfDepositTxsByMinutes)): ?>
    <script>
        new Chart(document.getElementById("txs-by-minutes-deposit").getContext('2d'), {
            type: 'line',
            data: {
                labels: JSON.parse('<?=json_encode(array_keys($countOfDepositTxsByMinutes))?>'),
                datasets: [
                    {
                        label: 'Deposit',
                        data: JSON.parse('<?=json_encode(array_values($countOfDepositTxsByMinutes))?>'),
                        backgroundColor: 'rgba(48, 155, 223, 0.6)',
                        borderColor: '#3498db',
                        lineTension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [{ticks: {suggestedMin: 0, stepSize: <?=$stepDeposit?>}}]
                }
            }
        });
    </script>
<?php endif; ?>