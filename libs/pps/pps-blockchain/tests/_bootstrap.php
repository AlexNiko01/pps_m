<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

if (file_exists(__DIR__ . '/../../../autoload.php')) {
    $vendor = __DIR__ . '/../../..';
    require_once "{$vendor}/autoload.php";
    require_once "{$vendor}/yiisoft/yii2/Yii.php";
} elseif (file_exists(__DIR__ . '/../../../../../vendor/autoload.php')) {
    $vendor = __DIR__ . '/../../../../../vendor';
    require_once "{$vendor}/autoload.php";
    require_once "{$vendor}/yiisoft/yii2/Yii.php";
} else {
    throw new Exception('File "autoload" not found');
}