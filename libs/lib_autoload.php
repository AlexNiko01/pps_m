<?php
function autoload($className)
{
    $filename = $className;
    $classNameArr = explode('\\', $className);
    if (in_array('pps', $classNameArr)) {
        $filename = '../../libs/' . $classNameArr[0] . '/pps-' . $classNameArr[1] . '/' . $classNameArr[2] . '.php';
    }

    if (file_exists($filename)) {
        include_once($filename);
    }
}


spl_autoload_register('autoload', true);
