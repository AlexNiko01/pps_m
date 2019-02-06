PPS Qiwi
====
PPS Qiwi is PPS extension which allows you to work with the qiwi payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-qiwi "dev-master"
```

or add

```
"pps/pps-qiwi": "dev-master"
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
                'qiwi' => [
                    'class' => pps\qiwi\Qiwi::class
                ],
            ]
        ]
    ],
```

```php
$qiwi = Yii::$app->pps->load('qiwi', [
    'token' => '4y0c129732107499770f3d0b0t733641'
]);
```