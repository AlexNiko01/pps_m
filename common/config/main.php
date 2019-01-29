<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'i18n' => [
            'translations' => [
                'yii' => [
                    'class' => yii\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                    'basePath' => '@yii/messages',
                ],
                '*' => [
                    'class' => yii\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                ],
            ],
        ],
    ],
    'modules' => [
        'user-management' => [
            'class' => webvimark\modules\UserManagement\UserManagementModule::class,

            'on beforeAction' => function (yii\base\ActionEvent $event) {
                if ($event->action->uniqueId === 'user-management/auth/login') {
                    $event->action->controller->layout = 'loginLayout.php';
                }
            },
        ]
    ],
];
