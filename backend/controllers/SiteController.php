<?php

namespace backend\controllers;

use backend\components\sender\TelegramSender;
use backend\models\Node;
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


    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return Yii::$app->user->loginRequired();
        }

        $children = Node::getCurrentNode()->getChildrenList(true, false);
        if ($children) {
            $queryParams['TransactionSearch']['brands'] = implode(',', array_keys($children));
        }


        $searchModel = new TransactionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
//        var_dump($dataProvider);
//        die();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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
