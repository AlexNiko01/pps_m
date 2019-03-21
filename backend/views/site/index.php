<?php
/**
 * @var $searchModelSystems \backend\models\search\PaymentSystemStatusSearch
 * @var $dataProviderSystems \yii\data\ActiveDataProvider
 * @var $searchModelProjects \backend\models\search\ProjectStatusSearch
 * @var $dataProviderProjects \\yii\data\ActiveDataProvider
 * @var $searchModelDeposit common\models\search\TransactionSearch
 * @var $searchModelWithdraw common\models\search\TransactionSearch
 * @var $dataProviderDeposit \yii\data\ActiveDataProvider
 * @var $dataProviderWithdraw \yii\data\ActiveDataProvider
 * @var $days integer
 * @var $stepDeposit integer
 * @var $stepWithdraw integer
 * @var $countOfDepositTxsByMinutes array
 * @var $countOfWithdrawTxsByMinutes array
 * @var $countOfDepositStatuses integer
 * @var $countOfWithdrawStatuses integer
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard';


?>
<?= Html::script('', ['src' => Url::to(['/js/chart.min.js'])]) ?>

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
    'countOfDepositStatuses' => $countOfDepositStatuses,
    'countOfWithdrawStatuses' => $countOfWithdrawStatuses,
]); ?>