PPS BitGo
====
PPS GoUrl is PPS extension which allows you to work with the BitGo payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-bitgo "dev-master"
```

or add

```
"pps/pps-bitgo": "dev-master"
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
                'bitgo' => [
                    'class' => pps\bitgo\BitGo::class
                ]
            ]
        ]
    ],
```

```php
$bitgo = Yii::$app->pps->load('bitgo', $PPSData)
```

For using withdraw method you should install [BitGoJs](https://github.com/BitGo/BitGoJS/) and run server ```./bin/bitgo-express --debug --port 3080 --env test --bind localhost```