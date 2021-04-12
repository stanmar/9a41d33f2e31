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

// Заполняем $n записями c ключами в диапазоне [0 - $n]
$n = (int)getenv('COUNT_ENTRIES');

$time = microtime(true);
$overwriteTestFound = false;
for ($i = 1; $i < $n; ++$i) {
    $key = random_int(1, $n);
    $value = random_int(1, 100);
    $put = $map->put($key, $value);
    if (!$overwriteTestFound && $put !== null) {
        echo 'Text complete. For key ' . $key . ' was:' . $put . ' new:' . $value . "\n";
        $overwriteTestFound = true;
    }
    if ($i % 100000 === 0) {
        $delta = microtime(true) - $time;
        $time += $delta;
        echo $i . " - $delta\n";
        gc_collect_cycles();
    }
}

exit(0);
