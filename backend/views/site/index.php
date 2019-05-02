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
        <div class="col-md-4 col-sm-12">
            <h3>Payment systems statuses</h3>
            <?php echo $this->render('_systems-statuses', [
                'searchModelSystems' => $searchModelSystems,
                'dataProviderSystems' => $dataProviderSystems
            ]); ?>
        </div>
        <div class="col-md-5  col-sm-12">
            <h3>Projects statuses</h3>
            <?php echo $this->render('_projects-statuses', [
                'searchModelProjects' => $searchModelProjects,
                'dataProviderProjects' => $dataProviderProjects
            ]); ?>
        </div>
        <div class="col-md-3  col-sm-12">
            <h3>Pss status</h3>
            <table class="kv-grid-table table table-bordered table-striped kv-table-wrap">
                <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th data-col-seq="1"><a href="/?dp-3-sort=name" data-sort="name">Name</a></th>
                    <th class="kv-align-center" style="width:90px;" data-col-seq="2">
                        <a href="/?dp-3-sort=active" data-sort="active">Active</a>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr data-key="1">
                    <td>1</td>
                    <td style="text-align: left;" data-col-seq="1">Pps</td>
                    <td class="kv-align-center" style="width:90px;" data-col-seq="2">
                        <?php if ($ppsClass) : ?>
                            <span class="glyphicon <?php echo $ppsClass; ?>"></span>
                        <?php endif; ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-6 no-gutter-right">
            <h3>Deposit transactions</h3>
            <?php echo $this->render('_deposit', [
                'searchModelDeposit' => $searchModelDeposit,
                'dataProviderDeposit' => $dataProviderDeposit
            ]); ?>

        </div>
        <div class="col-xl-6 no-gutter-left">
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