PPS PaySafeCard
====
PPS PaySafeCard is PPS extension which allows you to work with the PaySafeCard payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-paysafecard "dev-master"
```

or add

```
"pps/pps-paysafecard": "dev-master"
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
                'paysafecard' => [
                    'class' => pps\paysafecard\Neteller::class
                ]
            ]
        ]
    ],
```

```php
$paysafecard = Yii::$app->pps->load('paysafecard', $PPSData)
```