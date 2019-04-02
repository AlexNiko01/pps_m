<?php

namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;

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
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
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
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        Yii::$app->cache->flush();
        return $this->render('index');
    }

//    /**
//     * @return string
//     */
//    public function actionCommonComponentsExceptionSettingsexception()
//    {
//        return $this->render('common-components-exception-settingsexception');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionCommonComponentsHelpersLogger()
//    {
//        Yii::$app->cache->flush();
//
//        return $this->render('common-components-helpers-logger');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionCommonComponentsHelpersRestructuring()
//    {
//        return $this->render('common-components-helpers-restructuring');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionCommonComponentsInquirerPaymentsysteminquirer()
//    {
//        return $this->render('common-components-inquirer-paymentsysteminquirer');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionCommonComponentsSenderMessagesender()
//    {
//        return $this->render('common-components-sender-messagesender');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionCommonComponentsSenderRocketchatsender()
//    {
//        return $this->render('common-components-sender-rocketchatsender');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionCommonComponentsSenderSender()
//    {
//        return $this->render('common-components-sender-sender');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionCommonComponentsSenderTelegramsender()
//    {
//        return $this->render('common-components-sender-telegramsender');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionConsoleControllersCroncontroller()
//    {
//        return $this->render('console-controllers-croncontroller');
//    }
//
//    /**
//     * @return string
//     */
//    public function actionConsoleControllersNotificationcontroller()
//    {
//        return $this->render('console-controllers-notificationcontroller');
//    }


    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }


    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }
}
