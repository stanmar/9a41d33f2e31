<?php

spl_autoload_register(
    static function ($class) {
        $prefix = 'App';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = __DIR__ . '/app'. str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
);