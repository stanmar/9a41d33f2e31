<?php

namespace App;

/**
 * Интерфейс для хранилища основанного на разделяемой памяти
 */
interface SharedMemoryStorageInterface
{
    /**
     * Записываем значение в хранилище
     *
     * @param int $key
     * @param int $value
     *
     * @return int|null
     */
    public function put(int $key, int $value): ?int;

    /**
     * Читаем значение из хранилища
     *
     * @param int $key
     *
     * @return int|null
     */
    public function get(int $key): ?int;
}