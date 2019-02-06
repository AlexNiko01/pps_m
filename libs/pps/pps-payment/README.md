Payment
====
PPS Payment is PPS component which should inherit all payment systems.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pps/pps-payment "v1.0"
```

or add

```
"pps/pps-payment": "v1.0"
```

to the require section of your `composer.json` file.

Usage
-----

Once the extension is installed, just use it in your code to create a extension of payment system :

```php
<?php

namespace pps\paysys;

use pps\payment\Payment;

class PaySys extends Payment
{
    // Your code
}
```

The model of the payment system must implement the interface IModel:
```php
<?php

namespace pps\paysys;

use pps\payment\IModel;

class Model extends \yii\base\Model implements IModel
{
    // Your code
}
```