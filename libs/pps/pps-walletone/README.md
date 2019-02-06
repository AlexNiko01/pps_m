PPS Wallet One
====
PPS WalletOne is PPS extension which allows you to work with the Wallet One payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-walletone "dev-master"
```

or add

```
"pps/pps-walletone": "dev-master"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
'components' => [
        'pps' => [
            'class' => pps\core\Core::class,
            'payments' => [
                'walletone' => [
                    'class' => pps\walletone\WalletOne::class
                ]
            ]
        ]
    ],
```

```php
$walletone = Yii::$app->pps->load('walletone', $PPSData)
```