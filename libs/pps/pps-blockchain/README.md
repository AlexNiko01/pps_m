PPS Blockchain
====
PPS Blockchain is PPS extension which allows you to work with the Blockchain payment system.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-blockchain "dev-master"
```

or add

```
"pps/pps-blockchain": "dev-master"
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
                'blockchain' => [
                    'class' => pps\blockchain\Blockchain::class,
                    'wallet_url' => 'http://127.0.0.1:3000/', // url of service-my-wallet-v3
                    'confirmations' => 10, // Default 6
                    'fee' => 5000, // Fee for withdraw, default 10 000
                    'min_fee_per_byte' => 5, // Default 4
                    'auto_min_fee_per_byte' => true, // Default false
                ]
            ]
        ]
    ],
```

```php
$blockchain = Yii::$app->pps->load('blockchain', $PPSData)
```

For creating accounts and Withdraw
-
For create withdraw and bitcoin accounts you should install [blockchain-wallet](https://github.com/blockchain/service-my-wallet-v3) and set wallet_url to config for blockchain