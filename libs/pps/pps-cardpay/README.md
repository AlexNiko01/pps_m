PPS CardPay
====
PPS CardPay is PPS extension which allows you to work with the CardPay payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-cardpay "dev-master"
```

or add

```
"pps/pps-cardpay": "dev-master"
```

to the require section of your `composer.json` file.


Usage
-----
Run migrations
```bash
$ php yii migrate --migrationPath=vendor/pps/pps-cardpay/migrations
```

Once the extension is installed, simply use it in your code by  :

```php
'components' => [
        'pps' => [
            'class' => pps\core\Core::class,
            'payments' => [
                'cardpay' => [
                    'class' => pps\cardpay\CardPay::class
                ]
            ]
        ]
    ],
```

```php
$cardpay = Yii::$app->pps->load('cardpay', $PPSData)
```