PPS Cryptonator
====
PPS Cryptonator is PPS extension which allows you to work with the trio payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-cryptonator "*"
```

or add

```
"pps/pps-cryptonator": "dev-master"
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
                'cryptonator' => [
                    'class' => pps\cryptonator\Cryptonator::class
                ]
            ]
        ]
    ],
```

```php
$cryptonator = Yii::$app->pps->load('cryptonator', $PPSData)
```