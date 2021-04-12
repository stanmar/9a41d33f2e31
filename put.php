<?php

use App\IntIntMap;

require_once 'autoload.php';
error_reporting(E_ERROR | E_PARSE);

// Инициализация
$id = ftok("/tmp/shm", 't');

$shm_id = shmop_open($id, "w", 0, 0);
if (!$shm_id) {
    echo "\n  Error \n";
    exit (1);
}

$map = new IntIntMap($shm_id, 0);

if (!isset($_SERVER['argv'][1], $_SERVER['argv'][2])) {
    echo "\n Parameter key or value not found \n";
    exit (1);
}

[$script, $key, $value] = $_SERVER['argv'];

// Пробуем получить сохраненное значение по ключам в диапазоне (0 - $m)
$put = $map->put($key, $value);

echo "\n PUT For key $key and value $value --- result(last_value): $put \n";
exit(0);
