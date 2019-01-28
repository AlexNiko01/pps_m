<?php

namespace backend\controllers;

use backend\components\sender\TelegramSender;
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
        return $this->render('index');
    }


    public function actionLogin()
    {

        if (!\Yii::$app->user->isSuperadmin) {
            throw new ForbiddenHttpException();
        }
        $request = Yii::$app->request;

        $folders = ['api', 'backend', 'console', 'frontend'];

        $folder = $request->get('folder', 'api');
        $version = $request->get('v');

        if (!in_array($folder, $folders)) {
            throw new NotFoundHttpException('Folder not found');
        }

        $file = str_replace(['../', './', '/'], '', $request->get('file'));

        $root = dirname(Yii::getAlias('@app'));

        $dir = $root . '/' . $folder . '/runtime/logs/';

        if (!is_dir($dir)) {
            throw new NotFoundHttpException("Folder $folder/runtime/logs/ not found");
        }

        $log_files = array_diff(scandir($dir), ['.', '..']);

        $same_files = [];

        foreach ($log_files as $log_file) {
            if (preg_match('~\.log\.[0-9]+$~', $log_file)) {
                list($name, $ext, $ver) = explode('.', $log_file);
            } else {
                list($name, $ext) = explode('.', $log_file);
                $ver = null;
            }

            if (!isset($same_files[$name])) {
                if ($ver) {
                    $same_files[$name] = [$ver];
                } else {
                    $same_files[$name] = [];
                }
            } else {
                if ($ver) {
                    $same_files[$name][] = $ver;
                }
            }

            if (!$file) {
                $file = $name;
            }
        }

        $file_groups = [];

        foreach ($same_files as $f => $vs) {

            preg_match('~^(?<name>[\\w-]+)?\d{4}\-\d{2}\-\d{2}$~', $f, $m);

            if (isset($m['name'])) {
                $name = rtrim($m['name'], '-');
                $name = ucfirst($name);
                $name = str_replace('-', ' ', $name);
                $file_groups[$name][$f] = $vs;
            } else {
                $file_groups['server'][$f] = $vs;
            }
        }

        $filePath = $dir . $file . '.log' . ($version ? ".{$version}" : '');

        if (!is_file($filePath)) {
            throw new NotFoundHttpException('File not found.');
        } else {
            $content = file_get_contents($filePath);
        }

        return $this->render('log', [
            'folders' => $folders,
            'active_folder' => $folder,
            'groups' => $file_groups,
            'file' => $file,
            'version' => $version,
            'content' => $content,
        ]);
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
