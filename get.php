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

if (!isset($_SERVER['argv'][1])) {
    echo "\n Parameter key not found \n";
    exit (1);
}
$key = $_SERVER['argv'][1];

// Пробуем получить сохраненное значение по ключам в диапазоне (0 - $m)
$get = $map->get($key);

echo "\n For key $key found: $get \n";
exit(0);
