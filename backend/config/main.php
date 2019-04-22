<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => [
        'log',
        'common\components\bootstrap\EventBootstrap',
    ],
    'modules' => [
        'user-management' => [
            'class' => webvimark\modules\UserManagement\UserManagementModule::class,
            'on beforeAction' => function (yii\base\ActionEvent $event) {
                if ($event->action->uniqueId === 'user-management/auth/login') {
                    $event->action->controller->layout = 'loginLayout.php';
                }
            },

//            'controllerNamespace'=>'backend\controllers\UserManagement',
        ]
    ],
    'components' => [
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@vendor/webvimark/module-user-management/views' => '@backend/views/user-management',
                ],
            ],
        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'authLogService' => ['class' => common\components\auth\AuthLogService::class],
        'user' => [
            'class' => webvimark\modules\UserManagement\components\UserConfig::class,
            'on afterLogin' => function ($event) {
                \webvimark\modules\UserManagement\models\UserVisitLog::newVisitor($event->identity->id);
            },
        ],

        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'pattern' => '',
                    'route' => 'site/index'
                ],
                [
                    'pattern' => 'settings',
                    'route' => 'settings/index'
                ]
            ],
        ]
    ],
    'params' => $params,
];
