<?php

namespace App\HashTable;

/**
 * Корзина хэш таблицы.
 */
class Bucket
{
    /**
     * Размер данных корзины
     */
    public const SIZE = 8;

    /**
     * @param int $entry позиция первой записи по хэшу
     */
    public function __construct(private int $entry)
    {
        assert($this->entry > 0);
    }

    /**
     * @return int
     */
    public function getEntry(): int
    {
        return $this->entry;
    }
}