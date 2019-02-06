PPS Astropay
====
PPS Astropay is PPS extension which allows you to work with the astropay payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-astropay "dev-master"
```

or add

```
"pps/pps-astropay": "dev-master"
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
                'astropay' => [
                    'class' => pps\astropay\Astropay::class
                ],
            ]
        ]
    ],
```

```php
$astropay = Yii::$app->pps->load('astropay', [
    'x_login' => '1sfbyZrHlFjFWAdKoRSfuorMx0scDd5NS'
    'x_trans_key' => 'vmXVQqAo2Y9wn17rBMhiUsPQG7xorCEg'
    'secret_key' => '1DnlSe1kH3ULWfRRzRzFlcxa3sxNH4Y5'
]);
```