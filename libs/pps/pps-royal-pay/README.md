PPS Royal Pay
====
PPS Royal Pay is PPS extension which allows you to work with the royal-pay payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-royal-pay "dev-master"
```

or add

```
"pps/pps-royal-pay": "dev-master"
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
                'royal-pay' => [
                    'class' => pps\royalpay\RoyalPay::class
                ]
            ]
        ]
    ],
```

```php
$royalPay = Yii::$app->pps->load('royal-pay', [
    'auth_key' => 'authkey',
    'secret_key' => 'secretkey'
]);
```