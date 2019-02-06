PPS Cubits
====
PPS Cubits is PPS extension which allows you to work with the [Cubits payment system](https://cubits.com/).

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-cubits "dev-master"
```

or add

```
"pps/pps-cubits": "dev-master"
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
                'cubits' => [
                    'class' => pps\pps-cubits\Cubits::class,
                ]
            ]
        ]
    ],
```

```php
$blockchain = Yii::$app->pps->load('blockchain', [
    'secret_key' => 'OJ7C3DBkMScVk89fJllpKFujspsP9aa4KVnGa3DGVQXUA5lTaBK4eWtONQEg5pAX',
    'cubits_key' => '549653887407b9b8ad66d4b47093eb9f'
]);
```