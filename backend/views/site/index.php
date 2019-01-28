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
$statuses = \pps\payment\Payment::getStatuses()

?>
<?= Html::script('', ['src' => Url::to(['/js/chart.min.js'])]) ?>

<style>
    .panel {
        background: transparent;
        border: none;
        box-shadow: none;
    }

    .panel-body {
        padding: 0;
    }

    .statuses, .transactions, .merchants {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 0 5px rgba(33, 33, 33, .1);
        margin-bottom: 15px;
    }

    .transactions, .merchants {
        height: 200px;
        max-height: 400px;
        overflow: auto;
        padding: 6px;
    }
</style>
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3><?= $countOfClients ?></h3>
                <p>Clients</p>
            </div>
            <div class="icon">
                <i class="fa fa-users"></i>
            </div>
            <!--a href="<?= Url::to(['front-user/on-branch']) ?>" class="small-box-footer">More info <i
                        class="fa fa-arrow-circle-right"></i></a-->
        </div>
    </div>

    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h3><?= $countOfBrands ?></h3>

                <p>Brands</p>
            </div>
            <div class="icon">
                <i class="fa fa-shopping-basket"></i>
            </div>
            <!--a href="<?= Url::to(['front-node/details']) ?>" class="small-box-footer">More info <i
                        class="fa fa-arrow-circle-right"></i></a-->
        </div>
    </div>

    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3><?= $countOfPaymentSystems ?></h3>
                <p>Payment Systems</p>
            </div>
            <div class="icon">
                <i class="fa fa-credit-card"></i>
            </div>
            <!--a href="<?= Url::to(['payment-system/index']) ?>" class="small-box-footer">More info <i
                        class="fa fa-arrow-circle-right"></i></a-->
        </div>
    </div>

    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h3><?= $txsToday ?></h3>
                <p>Transactions for today</p>
            </div>
            <div class="icon">
                <i class="fa fa-cubes"></i>
            </div>
            <!--a href="<?= Url::to(['transaction/deposit']) ?>" class="small-box-footer">More info <i
                        class="fa fa-arrow-circle-right"></i></a-->
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="info-box bg-aqua">
            <span class="info-box-icon"><i class="fa fa-bookmark-o"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">All Transactions</span>
                <span class="info-box-number"><?= $all ?></span>

                <div class="progress">
                    <div class="progress-bar" style="width: <?= round($finishedPercent) ?>%"></div>
                </div>
                <span class="progress-description">
                        <?= round($finishedPercent, 2) ?>% are finished
                  </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fa fa-bookmark-o"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Deposit Transactions</span>
                <span class="info-box-number"><?= $countOfDeposit ?></span>

                <div class="progress">
                    <div class="progress-bar" style="width: <?= round($percentOfFinalDepositStatuses) ?>%"></div>
                </div>
                <span class="progress-description">
                        <?= round($percentOfFinalDepositStatuses, 2) ?>% has final status
                  </span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="info-box bg-yellow">
            <span class="info-box-icon"><i class="fa fa-bookmark-o"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Withdraw Transactions</span>
                <span class="info-box-number"><?= $countOfWithdraw ?></span>

                <div class="progress">
                    <div class="progress-bar" style="width: <?= round($percentOfFinalWithdrawStatuses) ?>%"></div>
                </div>
                <span class="progress-description">
                        <?= round($percentOfFinalWithdrawStatuses, 2) ?>% has final status
                  </span>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="statuses">
            <canvas id="statuses" width="400" height="300"></canvas>
            <input type="button" class="all-statuses btn btn-link btn-xs" value="Show Details">
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="statuses">
            <canvas id="deposit-statuses" width="400" height="300"></canvas>
            <input type="button" class="deposit-statuses btn btn-link btn-xs" value="Show Details">
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="statuses">
            <canvas id="withdraw-statuses" width="400" height="300"></canvas>
            <input type="button" class="withdraw-statuses btn btn-link btn-xs" value="Show Details">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-xs-12">
        <div class="transactions">
            <h4 class="text-center">Suspended transactions (count: <?= count($pendingTransactions) ?>)</h4>
            <?php if(!empty($pendingTransactions)): ?>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Way</th>
                    <th>PS</th>
                    <th>Buyer ID</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($pendingTransactions as $tx): ?>
                    <tr>
                        <td><?= Html::a($tx['id'], ['transaction/view', 'id' => $tx['id']], ['class' => 'btn btn-xs btn-link']) ?></td>
                        <td><?= $tx['way'] ?></td>
                        <td><?= $tx['payment_system'] ?></td>
                        <td><?= $tx['buyer_id'] ?></td>
                        <td><?= $statuses[$tx['status']] ?></td>
                        <td><?= date('Y-m-d H:s:i', $tx['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Suspended transactions not found!</p>
            <?php endif;?>
        </div>
    </div>
    <div class="col-lg-6 col-xs-12">
        <div class="merchants">
            <h4 class="text-center">Merchants (count: <?= count($txsByMerchant) ?>)</h4>
            <?php if (!empty($txsByMerchant)):?>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Count Trxs</th>
                    <th>Created At</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($txsByMerchant as $merchant): ?>
                    <tr>
                        <td><?= $merchant['brand_id'] ?></td>
                        <td><?= $merchant['name'] ?></td>
                        <td><?= $merchant['count'] ?></td>
                        <td><?= date('Y-m-d H:s:i', $merchant['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Merchants not found!</p>
            <?php endif;?>
        </div>
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
<div class="row">
    <div class="col-md-12">
        <div class="statuses">
            <h4 class="text-center" style="padding: 4px;">Count of withdraw transactions for <?= $days ?> days</h4>
            <?php if (!empty($countOfWithdrawTxsByMinutes)): ?>
                <canvas id="txs-by-minutes-withdraw" width="900" height="260"></canvas>
            <?php else: ?>
                <h5 class="text-center" style="padding: 20px;">Withdraw transactions not found</h5>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    function changeDetailView(e, char) {
        char.options.legend.display = !char.options.legend.display;
        if (char.options.legend.display) {
            e.target.value = 'Hide Details';
        } else {
            e.target.value = 'Show Details';
        }
        char.update();
    }

    let all = new Chart(document.getElementById("statuses").getContext('2d'), {
        type: 'pie',
        data: {
            labels: JSON.parse('<?=json_encode(array_keys($countOfStatuses))?>'),
            datasets: [{
                label: '# of Votes',
                data: JSON.parse('<?=json_encode(array_values($countOfStatuses))?>'),
                backgroundColor: [
                    '#95a5a6',
                    'grey',
                    '#3498db',
                    '#27ae60',
                    '#9b59b6',
                    '#e67e22',
                    '#e74c3c',
                    'brown',
                    'green',
                    'tomato'
                ],
                borderWidth: 1
            }]
        },
        options: {
            cutoutPercentage: 25,
            title: {
                display: true,
                text: 'All statuses',
                fontSize: 16
            },
            legend: {
                display: false,
                labels: {
                    boxWidth: 20
                }
            }
        }
    });
    let deposit = new Chart(document.getElementById("deposit-statuses").getContext('2d'), {
        type: 'pie',
        data: {
            labels: JSON.parse('<?=json_encode(array_keys($countOfDepositStatuses))?>'),
            datasets: [{
                label: '# of Votes',
                data: JSON.parse('<?=json_encode(array_values($countOfDepositStatuses))?>'),
                backgroundColor: [
                    '#95a5a6',
                    'grey',
                    '#3498db',
                    '#27ae60',
                    '#9b59b6',
                    '#e67e22',
                    '#e74c3c',
                    'brown',
                    'green',
                    'tomato'
                ],
                borderWidth: 1
            }]
        },
        options: {
            cutoutPercentage: 25,
            title: {
                display: true,
                text: 'Deposit statuses',
                fontSize: 16
            },
            legend: {
                display: false,
                labels: {
                    boxWidth: 20
                }
            }
        }
    });
    let withdraw = new Chart(document.getElementById("withdraw-statuses").getContext('2d'), {
        type: 'pie',
        data: {
            labels: JSON.parse('<?=json_encode(array_keys($countOfWithdrawStatuses))?>'),
            datasets: [{
                label: '# of Votes',
                data: JSON.parse('<?=json_encode(array_values($countOfWithdrawStatuses))?>'),
                backgroundColor: [
                    '#95a5a6',
                    'grey',
                    '#3498db',
                    '#27ae60',
                    '#9b59b6',
                    '#e67e22',
                    '#e74c3c',
                    'brown',
                    'green',
                    'tomato'
                ],
                borderWidth: 1
            }]
        },
        options: {
            cutoutPercentage: 25,
            title: {
                display: true,
                text: 'Withdraw statuses',
                fontSize: 16
            },
            legend: {
                display: false,
                labels: {
                    boxWidth: 20
                }
            }
        }
    });

    document.querySelector('.all-statuses').addEventListener('click', (e) => {
        changeDetailView(e, all);
    });

    document.querySelector('.deposit-statuses').addEventListener('click', (e) => {
        changeDetailView(e, deposit);
    });

    document.querySelector('.withdraw-statuses').addEventListener('click', (e) => {
        changeDetailView(e, withdraw);
    });
</script>

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

<?php if (!empty($countOfWithdrawTxsByMinutes)): ?>
    <script>
        new Chart(document.getElementById("txs-by-minutes-withdraw").getContext('2d'), {
            type: 'line',
            data: {
                labels: JSON.parse('<?=json_encode(array_keys($countOfWithdrawTxsByMinutes))?>'),
                datasets: [
                    {
                        label: 'Withdraw',
                        data: JSON.parse('<?=json_encode(array_values($countOfWithdrawTxsByMinutes))?>'),
                        backgroundColor: 'rgba(232, 76, 56, 0.6)',
                        borderColor: '#e74c3c',
                        lineTension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [{ticks: {suggestedMin: 0, stepSize: <?=$stepWithdraw?>}}]
                }
            }
        });
    </script>
<?php endif; ?>
