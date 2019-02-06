PPS DengiOnline
====
PPS DengiOnline is PPS extension which allows you to work with the DengiOnline payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-dengionline "dev-master"
```

or add

```
"pps/pps-dengionline": "dev-master"
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
                'dengionline' => [
                    'class' => pps\dengionline\DengiOnline::class
                ]
            ]
        ]
    ],
```

```php
$dengionline = Yii::$app->pps->load('dengionline', $PPSData)
```