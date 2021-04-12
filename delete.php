<?php

// Инициализация
$id = ftok("/tmp/shm", 't');

$shm_id = shmop_open($id, "w", 0, 0);
$result = shmop_delete($shm_id);

echo $result ? 'success' : 'failure';
echo "\n";

exit(0);
