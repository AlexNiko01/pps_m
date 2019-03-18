<?php

namespace backend\controllers;

use backend\models\Node;
use backend\models\PaymentSystemStatusSearch;
use backend\models\ProjectStatusSearch;
use common\models\Transaction;
use common\models\TransactionSearch;
use webvimark\components\BaseController;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use pps\payment\Payment;

/**
 * Site controller
 */
class SiteController extends BaseController
{
    const DEPOSIT_DAYS = 1;
    const WITHDRAW_DAYS = 1 / 48;
    const CACHE_TIME = 600;

    const GRAPH_INTERVAL = 60;
    const MAX_WITHDRAW = 1;
    const MAX_DEPOSIT = 1;
    const MIN_STEP_WITHDRAW = 1;
    const MIN_STEP_DEPOSIT = 1;
    const SECONDS_IN_DAY = 86400;
    const OBSCURE_DIVIDER = 20;

    public $freeAccessActions = ['index', 'set-timezone'];

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        if (!Yii::$app->user->isGuest) {

            return [
                'error' => [
                    'class' => 'yii\web\ErrorAction',
                ],
            ];
        }
    }

    /**
     * @param $searchModel TransactionSearch
     * @param $children
     * @return mixed
     */
    private function getWithdrawTransactions($searchModel, $children)
    {
        $queryParams = \Yii::$app->request->queryParams;
        $queryParams['TransactionSearch']['way'] = 'withdraw';
        if ($children) {
            $queryParams['TransactionSearch']['brands'] = implode(',', array_keys($children));
        }
        return $searchModel->search($queryParams);

    }

    /**
     * @param $searchModel TransactionSearch
     * @param $children
     * @return mixed
     */
    private function getDepositTransactions($searchModel, $children)
    {
        $queryParams = \Yii::$app->request->queryParams;
        $queryParams['TransactionSearch']['way'] = 'deposit';
        if ($children) {
            $queryParams['TransactionSearch']['brands'] = implode(',', array_keys($children));
        }
        return $searchModel->search($queryParams);
    }

    /**
     * @param array $brandsId
     * @param string $transactionType
     * @param integer $interval
     * @return array
     */
    private function getStatuses(array $brandsId, string $transactionType, int $interval): array
    {
        $way = Payment::WAY_WITHDRAW;
        if ($transactionType === 'deposit') {
            $way = Payment::WAY_DEPOSIT;
        }
        $andWere = [' > ', 'created_at', (time() - $interval)];
        $arr = Transaction::getCountOfStatuses([
            'way' => $way,
            'brand_id' => $brandsId,

        ], $andWere);
        $successfullyPsStatuses = [
            Payment::STATUS_CREATED,
            Payment::STATUS_PENDING,
            Payment::STATUS_SUCCESS
        ];

        foreach ($successfullyPsStatuses as $status) {
            unset($arr[Payment::getStatuses()[$status]]);
        }
        return $arr;
    }

    /**
     * @return string|\yii\web\Response
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return Yii::$app->user->loginRequired();
        }
        $children = Node::getCurrentNode()->getChildrenList(true, false);
        $searchModel = new TransactionSearch();

        $dataProviderDeposit = $this->getDepositTransactions($searchModel, $children);
        $dataProviderWithdraw = $this->getWithdrawTransactions($searchModel, $children);

        $searchModelSystems = new PaymentSystemStatusSearch();
        $dataProviderSystems = $searchModelSystems->search(\Yii::$app->request->queryParams);

        $searchModelProjects = new ProjectStatusSearch();
        $dataProviderProjects = $searchModelProjects->search(\Yii::$app->request->queryParams);

        $daysDeposit = self::DEPOSIT_DAYS;
        $daysWithdraw = self::WITHDRAW_DAYS;
        $cache = Yii::$app->cache;
        $brands = Node::getCurrentNode()->getChildrenList();
        $brandsId = array_keys($brands);
        $countOfDepositTxsByMinutes = $cache->getOrSet(
            ['actionIndex', 'countOfTxsByMinutes', 'deposit', $brandsId, 'v2'],
            function () use ($daysDeposit, $brandsId) {
                return Transaction::getCountOfTxsByMinutes($daysDeposit, self::GRAPH_INTERVAL, ['way' => 'deposit', 'brand_id' => $brandsId]);
            }, self::CACHE_TIME);
        $countOfWithdrawTxsByMinutes = $cache->getOrSet(
            ['actionIndex', 'countOfTxsByMinutes', 'withdraw', $brandsId, 'v2'],
            function () use ($daysWithdraw, $brandsId) {
                return Transaction::getCountOfTxsByMinutes($daysWithdraw, self::GRAPH_INTERVAL, ['way' => 'withdraw', 'brand_id' => $brandsId]);
            }, self::CACHE_TIME);

        if (!count($countOfDepositTxsByMinutes)) {
            $maxDeposit = self::MAX_DEPOSIT;
        } else {
            $maxDeposit = max(array_values($countOfDepositTxsByMinutes));
        }
        if (!count($countOfWithdrawTxsByMinutes)) {
            $maxWithdraw = self::MAX_WITHDRAW;
        } else {
            $maxWithdraw = max(array_values($countOfWithdrawTxsByMinutes));
        }

        $stepDeposit = round($maxDeposit / self::OBSCURE_DIVIDER);
        if ($stepDeposit < 1) {
            $stepDeposit = self::MIN_STEP_DEPOSIT;
        }
        $stepWithdraw = round($maxWithdraw / self::OBSCURE_DIVIDER);
        if ($stepWithdraw < 1) {
            $stepWithdraw = self::MIN_STEP_WITHDRAW;
        }

        $countOfDepositStatuses = $this->getStatuses($brandsId, 'deposit', self::DEPOSIT_DAYS * self::SECONDS_IN_DAY);
        $countOfWithdrawStatuses = $this->getStatuses($brandsId, 'withdraw', self::WITHDRAW_DAYS * self::SECONDS_IN_DAY);

        return $this->render('index', [
            'searchModelWithdraw' => $searchModel,
            'dataProviderWithdraw' => $dataProviderWithdraw,

            'searchModelDeposit' => $searchModel,
            'dataProviderDeposit' => $dataProviderDeposit,

            'searchModelSystems' => $searchModelSystems,
            'dataProviderSystems' => $dataProviderSystems,

            'searchModelProjects' => $searchModelProjects,
            'dataProviderProjects' => $dataProviderProjects,

            'stepDeposit' => $stepDeposit,
            'stepWithdraw' => $stepWithdraw,
            'countOfDepositTxsByMinutes' => $countOfDepositTxsByMinutes,
            'countOfWithdrawTxsByMinutes' => $countOfWithdrawTxsByMinutes,
            'countOfDepositStatuses' => $countOfDepositStatuses,
            'countOfWithdrawStatuses' => $countOfWithdrawStatuses,
        ]);
    }

    /**
     * @return string
     */
    public function actionLogin()
    {
        return $this->render('log');
    }

    /**
     * Logout action.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
