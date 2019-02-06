PPS Piastrix
====
PPS Piastrix is PPS extension which allows you to work with the piastrix payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-piastrix "dev-master"
```

or add

```
"pps/pps-piastrix": "dev-master"
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
                'piastrix' => [
                    'class' => pps\piastrix\Piastrix::class
                ],
            ]
        ]
    ],
```

```php
$piastrix = Yii::$app->pps->load('piastrix', [
    'shop_id' => 1000
    'secret_key' => 'secretkey'
]);
```