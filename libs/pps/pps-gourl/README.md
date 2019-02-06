PPS GoUrl
====
PPS GoUrl is PPS extension which allows you to work with the gourl.io payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-gourl "dev-master"
```

or add

```
"pps/pps-gourl": "dev-master"
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
                'gourl' => [
                    'class' => pps\gourl\GoUrl::class
                ]
            ]
        ]
    ],
```

```php
$gourl = Yii::$app->pps->load('gourl', $PPSData)
```