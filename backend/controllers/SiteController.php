<?php

namespace backend\controllers;

use backend\components\sender\TelegramSender;
use backend\models\Node;
use common\models\Transaction;
use common\models\TransactionSearch;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Site controller
 */
class SiteController extends Controller
{
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
     * @param $queryParams
     * @param $children
     * @return mixed
     */
    private function getWithdrawTransactions($searchModel,  $children)
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
     * @param $queryParams
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
        $dataProviderWithdraw = $this->getWithdrawTransactions($searchModel, $children);
        $dataProviderDeposit = $this->getDepositTransactions($searchModel, $children);

        return $this->render('index', [
            'searchModelWithdraw' => $searchModel,
            'dataProviderWithdraw' => $dataProviderWithdraw,
            'searchModelDeposit' => $searchModel,
            'dataProviderDeposit' => $dataProviderDeposit
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
