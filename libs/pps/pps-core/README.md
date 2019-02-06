PPS Core
=============
PPS Core is PPS component witch interaction with payment system extensions.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-core "*"
```

or add

```
"pps/pps-core": "^1.0.0"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply add it in your config file :

```php
'components' => [
        'pps' => [
            'class' => pps\core\Core::class,
            'payments' => [
                'trio' => [
                    'class' => pps\trio\Trio::class
                ],
                'royal-pay' => [
                    'class' => pps\royalpay\RoyalPay::class
                ]
            ]
        ],
    ],
```

```php
$payment = Yii::$app->pps->load('trio')
```