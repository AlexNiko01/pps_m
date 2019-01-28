<?php
/**
 * @var $this yii\web\View
 */

use backend\components\LteMenu;
use webvimark\modules\UserManagement\UserManagementModule;

?>

<?= LteMenu::widget([
    'items' => [
        [
            'label' => 'Dashboard',
            'icon' => '<i class="fa fa-home"></i>',
            'url' => ['/site/index'],
        ],
        [
            'label' => 'Node management',
            'icon' => '<i class="fa fa-plus-square-o"></i>',
            'url' => ['/front-node/details'],
        ],
        [
            'label' => 'Users',
            'icon' => '<i class="fa fa-users"></i>',
            'url' => '#',
            'items' => [
                [
                    'label' => 'On selected node',
                    'url' => ['/front-user/index'],
                ],
                [
                    'label' => 'On branch',
                    'url' => ['/front-user/on-branch'],
                ],
            ],
        ],
        [
            'label' => 'Global Payment Systems',
            'icon' => '<i class="fa fa-globe"></i>',
            'url' => ['/payment-system/index'],
            'visible' => \Yii::$app->user->isSuperadmin
        ],
        [
            'label' => (\Yii::$app->user->isSuperadmin ? 'Users Payment Systems' : 'Payment Systems'),
            'icon' => '<i class="fa fa-credit-card"></i>',
            'url' => ['/user-payment-system/index'],
        ],
        [
            'label' => 'Transactions',
            'icon' => '<i class="fa fa-cubes"></i>',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Deposit',
                    'url' => ['/transaction/deposit'],
                ],
                [
                    'label' => 'Withdraw',
                    'url' => ['/transaction/withdraw'],
                ],
            ],
        ],
        [
            'label' => 'Statements',
            'icon' => '<i class="fa fa-file"></i>',
            'url' => ['/statement/index'],
        ],
        [
            'label' => 'Settings',
            'visible' => \Yii::$app->user->isSuperadmin,
            'icon' => '<i class="fa fa-sliders"></i>',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Telegram',
                    'icon' => '<i class="fa fa-telegram"></i>',
                    'url' => ['telegram/index'],
                ],
                [
                    'label' => 'PPS Settings',
                    'icon' => '<i class="fa fa-gear"></i>',
                    'url' => ['settings/index'],
                ],
            ],
        ],
        [
            'label' => 'Requests',
            'icon' => '<i class="fa fa-exchange"></i>',
            'url' => ['/requests/index'],
            'visible' => \Yii::$app->user->isSuperadmin
        ],
        [
            'label' => 'Sys info',
            'icon' => '<i class="fa fa-info"></i>',
            'url' => '#',
            'visible' => \Yii::$app->user->isSuperadmin,
            'items' => [
                [
                    'label' => 'Buyer Logs',
                    'url' => ['/buyer-log/index']
                ],
                [
                    'label' => 'Temporary Dep. Links',
                    'url' => ['/temporary-deposit-link/index']
                ],
                [
                    'label' => 'Addresses',
                    'url' => ['/user-address/index']
                ],
                [
                    'label' => 'Hdbk Requests Types',
                    'url' => ['/hdbk-requests-type/index']
                ]
            ]
        ],
        [
            'label' => 'Test API app',
            'icon' => '<i class="fa fa-exchange"></i>',
            'url' => ['/test-api/index']
        ],
        '999998' => [
            'label' => 'Mega users',
            'icon' => '<i class="fa fa-balance-scale"></i>',
            'url' => '#',
            'items' => UserManagementModule::menuItems(),
        ],
        '999999' => [
            'label' => 'Dev junk',
            'icon' => '<i class="fa fa-beer"></i>',
            'url' => '#',
            'items' => [
                [
                    'label' => 'Migrations',
                    'url' => ['/migrations/default/index']
                ],
                [
                    'label' => 'Gii',
                    'url' => ['/gii/default/index'],
                    'visible' => YII_ENV_DEV && \Yii::$app->user->isSuperadmin
                ],
                [
                    'label' => 'Get error',
                    'url' => ['/site/log']
                ],
                [
                    'label' => 'History of transaction statuses',
                    'url' => ['/history-transaction-status/index']
                ],
            ],
        ],
    ],
]);
