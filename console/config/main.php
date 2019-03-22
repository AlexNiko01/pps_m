<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
        ],
    ],
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['settings'],
                    'logFile' => '@runtime/logs/settings/settings.log',
                    'enabled' => YII_DEBUG,
                ],
            ],
        ],
        'telegram' => function () {
            $telegram = new aki\telegram\Telegram;
            try {
                $botToken = \backend\models\Settings::getValue('bot_token');
            } catch (common\components\exception\SettingsException $e) {
                \Yii::info($e->getMessage(), 'settings');
                return null;
            }

            $telegram->botToken = $botToken;
            return $telegram;
        }
        ,
        'sender' => function () {
            $sender = new common\components\sender\MessageSender;
            $sender->addSender(new common\components\sender\RocketChatSender);
//            $sender->addSender(new common\components\sender\TelegramSender);
            return $sender;
        },
        'inquirer' => [
            'class' => 'common\components\inquirer\PaymentSystemInquirer'
        ]
    ],
    'params' => $params,
];
