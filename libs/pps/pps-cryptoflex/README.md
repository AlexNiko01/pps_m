PPS CryptoFlex
====
PPS CryptoFlex is PPS extension which allows you to work with the CryptoFlex payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-cryptoflex "dev-master"
```

or add

```
"pps/pps-cryptoflex": "dev-master"
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
                'cryptoflex' => [
                    'class' => pps\cryptoflex\CryptoFlex::class
                ]
            ]
        ]
    ],
```

```php
$cryptoflex = Yii::$app->pps->load('cryptoflex', $PPSData)
```