<?php

spl_autoload_register(function($class) {
    if (strpos($class, 'Hexagon\\') === 0) {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = __DIR__ . DIRECTORY_SEPARATOR . "$class.php";

        if (file_exists($file)) {
            require $file;
        }
    }
});

require_once __DIR__ . "/vendor/Artax/autoload.php";
require_once __DIR__ . "/vendor/Auryn/src/bootstrap.php";