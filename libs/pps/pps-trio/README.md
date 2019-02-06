PPS Trio
====
PPS Trio is PPS extension which allows you to work with the trio payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-trio "dev-master"
```

or add

```
"pps/pps-trio": "dev-master"
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
                'trio' => [
                    'class' => pps\trio\Trio::class
                ],
            ]
        ]
    ],
```

```php
$trio = Yii::$app->pps->load('trio', [
    'shop_id' => 1000
    'secret_key' => 'secretkey'
]);
```