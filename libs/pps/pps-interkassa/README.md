PPS Cryptonator
====
PPS Interkassa is PPS extension which allows you to work with the Interkassa payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-interkassa "dev-master"
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
                'interkassa' => [
                    'class' => pps\interkassa\Interkassa::class
                ]
            ]
        ]
    ],
```

```php
$interkassa = Yii::$app->pps->load('interkassa', [
    'user_id' => '0123456789abcdefghijklmn',
    'api_key' => 'abcdefghijklmnopqrstuvwxyz123456',
    'shop_id' => '1234567890abcdefghijklmn',
    'secret_key' => 'abcdefghijklmnop',
])
```