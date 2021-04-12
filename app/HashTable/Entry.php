<?php

namespace App\HashTable;

/**
 * Запись в хэш таблице
 */
class Entry
{
    /**
     * Размер записи в байтах
     */
    public const SIZE = 24;

    /**
     * Позиция в памяти в байтах
     *
     * @var int|null
     */
    private ?int $offset;

    /**
     * @param int $key Ключ
     * @param int $value Значение
     * @param int $next Адрес следующего значения в случае коллизии
     */
    public function __construct(private int $key, private int $value, private int $next)
    {
        assert($this->key > 0);
        assert($this->value > 0);
        assert($this->next >= 0);
    }

    /**
     * @return int
     */
    public function getKey(): int
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getNext(): int
    {
        return $this->next;
    }

    /**
     * @param int $value
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * @param int $next
     */
    public function setNext(int $next): void
    {
        $this->next = $next;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }
}