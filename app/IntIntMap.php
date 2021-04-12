<?php

namespace App;

use App\HashTable\HashTable;
use Shmop;

/**
 * Требуется написать IntIntMap, который по произвольному int ключу хранит произвольное int значение
 * Важно: все данные (в том числе дополнительные, если их размер зависит от числа элементов) требуется хранить в выделенном заранее блоке в разделяемой памяти
 * для доступа к памяти напрямую необходимо (и достаточно) использовать следующие два метода:
 * \shmop_read и \shmop_write
 */
class IntIntMap
{
    /**
     * Хэш таблица в разделяемой памяти
     *
     * @var SharedMemoryStorageInterface
     */
    protected SharedMemoryStorageInterface $storage;

    /**
     * @param Shmop $shm_id результат вызова \shmop_open
     * @param int $size размер зарезервированного блока в разделяемой памяти (~100GB)
     */
    public function __construct(Shmop $shm_id, int $size)
    {
        $this->storage = new HashTable($shm_id, $size);
    }

    /**
     * Метод должен работать со сложностью O(1) при отсутствии коллизий, но может деградировать при их появлении
     *
     * @param int $key произвольный ключ
     * @param int $value произвольное значение
     *
     * @return int|null предыдущее значение
     */
    public function put(int $key, int $value): ?int
    {
        return $this->storage->put($key, $value);
    }

    /**
     * Метод должен работать со сложностью O(1) при отсутствии коллизий, но может деградировать при их появлении
     *
     * @param int $key ключ
     *
     * @return int|null значение, сохраненное ранее по этому ключу
     */
    public function get(int $key): ?int
    {
        return $this->storage->get($key);
    }
}