PPS Query Builder
====
PPS Query Builder is PPS extension which allows build queries very simple

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
php composer.phar require --prefer-dist pps/pps-querybuilder "dev-master"
or
composer require --prefer-dist pps/pps-querybuilder "0.0.5"
```

or add

```
"pps/pps-querybuilder": "0.0.5"
```

to the require section of your `composer.json` file.


Usage
-----

GET query:

```php
$request = (new QueryBuilder($url))
    ->setParams([
        'param1' => 'value1'
    ])
    ->send();
$decode_json_or_xml = true;
$response = $request->getResponse($decode_json_or_xml);
$info = $request->getInfo();
$error = $request->getError();
```

POST query:

```php
$request = (new QueryBuilder($url))
    ->setParams([
        'param1' => 'value1'
    ])
    ->setHeader('Content-Type', 'application/json') // or ->json()
    ->setOption(CURLOPT_TIMEOUT, 20)
    ->asPost()
    ->send();

$response = $request->getResponse(true);
```