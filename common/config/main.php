<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'timeZone' => 'Europe/Kiev',
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
        'pps' => [
            'class' => pps\core\Core::class,
            'payments' => [
                //useless:
                'trio' => [
                    'class' => pps\trio\Trio::class,
                ],
                //useless:
                'royal-pay' => [
                    'class' => pps\royalpay\RoyalPay::class,
                    'url' => 'https://royal-pay.com/api'
                ],
//                'testps' => [
//                    'class' => pps\testps\TestPS::class
//                ],
                'cryptonator' => [
                    'class' => pps\cryptonator\Cryptonator::class,
                    'url' => 'https://api.cryptonator.com/api/'
                ],
                'freekassa' => [
                    'class' => pps\freekassa\FreeKassa::class,
                    'url' => 'https://www.free-kassa.ru/api.php'
                ],
                'interkassa' => [
                    'class' => pps\interkassa\Interkassa::class,
                    'url' => 'https://api.interkassa.com/'
                ],
                'nixmoney' => [
                    'class' => pps\nixmoney\NixMoney::class,
                    'url' => 'https://www.nixmoney.com/'
                ],
                //weird so much....
                'gourl' => [
                    'class' => pps\gourl\GoUrl::class,
                    'url' => 'https://gourl.io/api.html'
                ],
                //weird so much....
                'ecommpay' => [
                    'class' => pps\ecommpay\Ecommpay::class,
                    'url' => 'https://gate-sandbox.accentpay.com/'
                ],
                'blockchain' => [
                    'class' => pps\blockchain\Blockchain::class,
                    'url' => 'https://www.blockchain.com/api/api_receive'

                ],
                'walletone' => [
                    'class' => pps\walletone\WalletOne::class,
                    'url' => 'https://wl.walletone.com/checkout/checkout/Index'
                ],
                'dengionline' => [
                    'class' => pps\dengionline\DengioOnline::class,
                    'url' => ''
                ],
                'skrill' => [
                    'class' => pps\skrill\Skrill::class,
                    'url' => ''
                ],
                'paysafecard' => [
                    'class' => pps\paysafecard\PaySafeCard::class,
                    'url' => ''
                ],
                'cardpay' => [
                    'class' => pps\cardpay\CardPay::class,
                    'url' => 'https://cardpay.com/MI/api/v2',
                    'sandbox' => false
                ],
                'zotapay' => [
                    'class' => pps\zotapay\ZotaPay::class,
                    'sandbox' => true,
                    'url' => ''
                ],
                'piastrix' => [
                    'class' => pps\piastrix\Piastrix::class,
                    'url' => 'https://core.piastrix.com'
                ],
                'cubits' => [
                    'class' => pps\cubits\Cubits::class,
                ],
                'bitgo' => [
                    'class' => pps\bitgo\BitGo::class,
                    'sandbox' => false,
                    'url' => ''
                ],
                'qiwi' => [
                    'class' => pps\qiwi\Qiwi::class,
                    'url' => 'https://pay-qiwi.com/api/qiwi/'
                ],
                'astropay' => [
                    'class' => pps\astropay\Astropay::class,
                    'sandbox' => true,
                    'url' => ''
                ],
                'fondy' => [
                    'class' => pps\fondy\Fondy::class,
                    'url' => ''
                ],
                'cryptoflex' => [
                    'class' => pps\cryptoflex\CryptoFlex::class,
                    'sandbox' => true,
                    'url' => ''
                ],
            ]
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
        ],
        'gridview' => [
            'class' => '\kartik\grid\Module',
        ],
    ]
];
