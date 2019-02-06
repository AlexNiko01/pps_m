PPS Ecommpay
====
PPS Ecommpay is PPS extension which allows you to work with the Ecommpay payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-ecommpay "dev-master"
```

or add

```
"pps/pps-ecommpay": "dev-master"
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
                'ecommpay' => [
                    'class' => pps\ecommpay\Ecommpay::class
                ]
            ]
        ]
    ],
```

```php
$ecommpay = Yii::$app->pps->load('ecommpay', $PPSData)
```