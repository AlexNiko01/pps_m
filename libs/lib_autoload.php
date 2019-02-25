<?php
function autoload($filename)
{
    $classNameArr = explode('\\', $filename);
    if (in_array('pps', $classNameArr)) {
        $path = __DIR__ . '/' . $classNameArr[0] . '/pps-' . $classNameArr[1];
        if (is_dir($path)) {
            foreach ($classNameArr as $iteration => $dir) {
                if ($iteration < 2) {
                    continue;
                }
                if ($dir) {
                    $path .= '/' . $dir;
                }
            }
        }
        $path = $path . '.php';
        if (file_exists($path)) {
            include_once($path);
        }
    }
}

spl_autoload_register('autoload', true);