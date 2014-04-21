<?php

spl_autoload_register(function($class) {
    if (strpos($class, 'Bit\\') === 0) {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = __DIR__ . "/src/$class.php";

        if (file_exists($file)) {
            require $file;
        }
    }
});

require_once __DIR__ . "/lib/Artax/autoload.php";
