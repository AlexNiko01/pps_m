<?php

namespace backend\controllers;

use backend\models\Node;
use backend\models\PaymentSystemStatusSearch;
use backend\models\ProjectStatusSearch;
use common\models\Transaction;
use common\models\TransactionSearch;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use pps\payment\Payment;

/**
 * Site controller
 */
class SiteController extends Controller
{
    const DEPOSIT_INTERVAL = 1440;
    const WITHDRAW_INTERVAL = 30;
    const CACHE_TIME = 600;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
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

    private function getSuccesfullPsStatusesNames($arr)
    {
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


//          TODO: add to constants

        $days = 1;
        $cache = Yii::$app->cache;
        $brands = Node::getCurrentNode()->getChildrenList();
        $brandsId = array_keys($brands);
        $countOfDepositTxsByMinutes = $cache->getOrSet(['actionIndex', 'countOfTxsByMinutes', 'deposit', $brandsId, 'v2'], function () use ($days, $brandsId) {
            return Transaction::getCountOfTxsByMinutes($days, self::DEPOSIT_INTERVAL, ['way' => 'deposit', 'brand_id' => $brandsId]);
        }, self::CACHE_TIME);
        $countOfWithdrawTxsByMinutes = $cache->getOrSet(['actionIndex', 'countOfTxsByMinutes', 'withdraw', $brandsId, 'v2'], function () use ($days, $brandsId) {
            return Transaction::getCountOfTxsByMinutes($days, self::WITHDRAW_INTERVAL, ['way' => 'withdraw', 'brand_id' => $brandsId]);
        }, self::CACHE_TIME);

        if (!count($countOfDepositTxsByMinutes)) {
            $maxDeposit = 1;
        } else {
            $maxDeposit = max(array_values($countOfDepositTxsByMinutes));
        }

        if (!count($countOfWithdrawTxsByMinutes)) {
            $maxWithdraw = 1;
        } else {
            $maxWithdraw = max(array_values($countOfWithdrawTxsByMinutes));
        }

        $stepDeposit = round($maxDeposit / 20);
        if ($stepDeposit < 1) {
            $stepDeposit = 1;
        }
        $stepWithdraw = round($maxWithdraw / 20);
        if ($stepWithdraw < 1) {
            $stepWithdraw = 1;
        }


        $countOfDepositStatuses = Transaction::getCountOfStatuses([
            'way' => Payment::WAY_DEPOSIT,
            'brand_id' => $brandsId
        ]);
        $countOfDepositStatuses = $this->getSuccesfullPsStatusesNames($countOfDepositStatuses);

        $countOfWithdrawStatuses = Transaction::getCountOfStatuses([
            'way' => Payment::WAY_WITHDRAW,
            'brand_id' => $brandsId
        ]);
        $countOfWithdrawStatuses = $this->getSuccesfullPsStatusesNames($countOfWithdrawStatuses);

        return $this->render('index', [
            'searchModelWithdraw' => $searchModel,
            'dataProviderWithdraw' => $dataProviderWithdraw,

            'searchModelDeposit' => $searchModel,
            'dataProviderDeposit' => $dataProviderDeposit,

            'searchModelSystems' => $searchModelSystems,
            'dataProviderSystems' => $dataProviderSystems,

            'searchModelProjects' => $searchModelProjects,
            'dataProviderProjects' => $dataProviderProjects,

            'days' => $days,
            'stepDeposit' => $stepDeposit,
            'stepWithdraw' => $stepWithdraw,
            'countOfDepositTxsByMinutes' => $countOfDepositTxsByMinutes,
            'countOfWithdrawTxsByMinutes' => $countOfWithdrawTxsByMinutes,
            'countOfDepositStatuses' => $countOfDepositStatuses,
            'countOfWithdrawStatuses' => $countOfWithdrawStatuses,
        ]);

    }


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
