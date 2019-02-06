PPS ZotaPay
====
PPS ZotaPay is PPS extension which allows you to work with the ZotaPay payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-zotapay "dev-master"
```

or add

```
"pps/pps-zotapay": "dev-master"
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
                'zotapay' => [
                    'class' => pps\zotapay\ZotaPay::class
                ]
            ]
        ]
    ],
```

```php
$cardpay = Yii::$app->pps->load('zotapay', $PPSData)
```