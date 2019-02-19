<?php
function autoload($filename)
{
    $classNameArr = explode('\\', $filename);


    $path = __DIR__ . '/' . $classNameArr[0] . '/pps-' . $classNameArr[1] . '/' . $classNameArr[2];

    if (in_array('pps', $classNameArr)) {
        if (!is_dir($path)) {
            $filename = $path . '.php';
        } else {
            $filename = $path . '/' . $classNameArr[3] . '.php';
        }
    }

    if (file_exists($filename)) {
        include_once($filename);
    }


}

spl_autoload_register('autoload', true);
