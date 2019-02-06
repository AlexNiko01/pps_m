PPS Free Kassa
====
PPS Free Kassa is PPS extension which allows you to work with the free-kassa payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-freekassa "dev-master"
```

or add

```
"pps/pps-freekassa": "dev-master"
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
                'freekassa' => [
                    'class' => pps\freekassa\FreeKassa::class
                ]
            ]
        ]
    ],
```

```php
$freekassa = Yii::$app->pps->load('freekassa', $PPSData)
```