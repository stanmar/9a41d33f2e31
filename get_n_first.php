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

// Пробуем получить сохраненное значение по ключам в диапазоне (0 - $m)
$m = (int)getenv('COUNT_KEYS');
for ($i = 1; $i < $m; ++$i) {
    $get = $map->get($i);
    echo "GET for $i : $get\n";
}

exit(0);
