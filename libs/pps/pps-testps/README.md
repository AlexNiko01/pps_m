PPS TestPS
====
PPS TestPS is PPS extension which allows you to test api.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-testps "dev-master"
```

or add

```
"pps/pps-testps": "dev-master"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
'components' => [
        'testps' => [
            'class' => pps\core\Core::class,
            'payments' => [
                'testps' => pps\testps\TestPS::class
            ]
        ]
    ],
```

```php
$testps = Yii::$app->pps->load('testps', [
    'example_key' => 'examplekey'
])
```