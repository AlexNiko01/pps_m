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
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'telegram' => [
            'class' => 'aki\telegram\Telegram',
            'botToken' => '703592142:AAGedZspWYQ9Ba7h29JOjWr_NfjtFCumy5Y',
        ],
        'sender' => function () {
            $sender = new backend\components\sender\MessageSender;
//            $sender->addSender(new backend\components\sender\RocketChatSender);
            $sender->addSender(new backend\components\sender\TelegramSender);

            return $sender;
        },
        'inquirer' => [
            'class' => 'backend\components\inquirer\PaymentSystemInquirer'
        ]
    ],
    'params' => $params,
];
