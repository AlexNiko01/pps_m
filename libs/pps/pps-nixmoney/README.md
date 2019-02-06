PPS Nix Money
====
PPS NixMoney is PPS extension which allows you to work with the Nix Money payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-nixmoney "dev-master"
```

or add

```
"pps/pps-nixmoney": "dev-master"
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
                'nixmoney' => [
                    'class' => pps\nixmoney\NixMoney::class
                ]
            ]
        ]
    ],
```

```php
$nixmoney = Yii::$app->pps->load('nixmoney', $PPSData)
```