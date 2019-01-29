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
    <div class="col-lg-6 col-xs-12">

    </div>
    <div class="col-lg-6 col-xs-12">

    </div>
</div>