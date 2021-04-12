<?php

use App\IntIntMap;

require_once 'autoload.php';

error_reporting(E_ERROR | E_PARSE);

// Инициализация
$id = ftok("/tmp/shm", 't');

$blockSize = (int)getenv('SHARED_MEMORY_BLOCK');
$shm_id = shmop_open($id, "n", 0644, $blockSize);
if (!$shm_id) {
    echo "\nCannot allocate memory or already inited\n";
    exit (1);
}

$map = new IntIntMap($shm_id, $blockSize);

exit(0);
