<?php

namespace App\HashTable;

use App\SharedMemoryStorageInterface;
use RuntimeException;
use Shmop;
use Throwable;

/**
 * Хэш таблица
 */
class HashTable implements SharedMemoryStorageInterface
{
    /**
     * @var MetaData
     */
    protected MetaData $metaData;

    /**
     * Создание кэш таблицы
     *
     * @param Shmop $shm_id Идентификатор для доступа к выделенной области в разделяемой памяти
     * @param int $totalAllocatedBytes Размер выделенного блока памяти в байтах
     */
    public function __construct(
        protected Shmop $shm_id,
        int $totalAllocatedBytes
    ) {
        $metaData = MetaData::load($shm_id);

        if ($metaData instanceof MetaData) {
            $this->metaData = $metaData;
        } else {
            $this->metaData = MetaData::init($shm_id, $totalAllocatedBytes);
            $this->metaData->save();
        }
    }

    /**
     * Запись значения в таблицу
     *
     * @param int $key
     * @param int $value
     *
     * @return int|null
     */
    public function put(int $key, int $value): ?int
    {
        $hash = $this->calcHash($key);

        $bucket = $this->getBucket($hash);
        if (!$bucket) {
            $bucket = new Bucket($this->metaData->getLastAddedEntryPosition() + Entry::SIZE);
            $this->saveBucket($hash, $bucket);
        }

        $firstEntry = $this->getEntry($bucket->getEntry());

        // Создание первой записи
        if (!$firstEntry) {
            $firstEntry = new Entry($key, $value, 0);
            $this->saveEntry($firstEntry, $this->metaData->getLastAddedEntryPosition() + Entry::SIZE);

            $this->metaData->setLastAddedEntryPosition($this->metaData->getLastAddedEntryPosition() + Entry::SIZE);
            $this->metaData->save();
            // Так как предыдущее значения не было, возвращаем ничего
            return null;
        }

        // Обновление первой записи
        if ($firstEntry->getKey() === $key) {
            if ($firstEntry->getValue() === $value) {
                return $value;
            }

            $oldValue = $firstEntry->getValue();
            $firstEntry->setValue($value);
            $this->saveEntry($firstEntry);

            return $oldValue;
        }

        // Работа с односвязным списком
        $prevEntry = $firstEntry;
        while (true) {
            $nextEntry = $this->getEntry($prevEntry->getNext());

            if ($nextEntry && $nextEntry->getKey() === $key) {
                if ($nextEntry->getValue() === $value) {
                    return $value;
                }

                $oldValue = $nextEntry->getValue();
                $nextEntry->setValue($value);
                $this->saveEntry($nextEntry);

                return $oldValue;
            }

            if (!$nextEntry) {
                $nextEntry = new Entry($key, $value, 0);
                $newEntryPosition = $this->metaData->getLastAddedEntryPosition() + Entry::SIZE;
                $this->saveEntry($nextEntry, $newEntryPosition);
                $this->metaData->setLastAddedEntryPosition($newEntryPosition);
                $this->metaData->save();
                // привязываем к предыдущему элементу
                $prevEntry->setNext($newEntryPosition);
                $this->saveEntry($prevEntry);

                return null;
            }

            $prevEntry = $nextEntry;
        }
    }

    /**
     * Чтение значения из таблицы
     *
     * @param int $key
     *
     * @return int|null
     */
    public function get(int $key): ?int
    {
        $hash = $this->calcHash($key);
        // Пробуем получить данные корзины
        $bucket = $this->getBucket($hash);

        // если не найдена корзина
        if (!$bucket) {
            return null;
        }

        $firstEntry = $this->getEntry($bucket->getEntry());

        if (!$firstEntry) {
            throw new RuntimeException('found bucket without entries');
        }

        // Если найдена в первой записи
        if ($firstEntry->getKey() === $key) {
            return $firstEntry->getValue();
        }

        // В случае коллизии идем по списку
        while (true) {
            $nextEntry = $this->getEntry($firstEntry->getNext());
            if (!$nextEntry) {
                return null;
            }

            if ($nextEntry->getKey() === $key) {
                return $nextEntry->getValue();
            }

            $firstEntry = $nextEntry;
        }
    }

    /**
     * Получение корзины по хэшу, если есть
     *
     * @param int $hash
     *
     * @return Bucket|null
     */
    private function getBucket(int $hash): ?Bucket
    {
        $entryStr = shmop_read($this->shm_id, $hash, PHP_INT_SIZE);
        $entry = (int)unpack('q*', $entryStr)[1];
        if ($entry === 0) {
            return null;
        }

        return new Bucket($entry);
    }

    /**
     * Сохранение корзины
     *
     * @param int $offset
     * @param Bucket $bucket
     *
     * @return void
     */
    private function saveBucket(int $offset, Bucket $bucket): void
    {
        if ($offset + Bucket::SIZE > $this->metaData->getTotalBuckets() * Bucket::SIZE) {
            throw new RuntimeException('memory limit for buckets exceeded');
        }
        $bucketStr = pack("q*", $bucket->getEntry());
        shmop_write($this->shm_id, $bucketStr, $offset);
    }


    /**
     * Получение записи в таблице
     *
     * @param int $offset
     *
     * @return Entry|null
     */
    private function getEntry(int $offset): ?Entry
    {
        if ($offset === 0) {
            return null;
        }

        $entryStr = shmop_read($this->shm_id, $offset, Entry::SIZE);

        try {
            $entry = new Entry(...array_values(unpack('q*', $entryStr)));
            $entry->setOffset($offset);

            return $entry;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Сохранение записи
     *
     * @param Entry $entry
     * @param int|null $offset
     *
     * @return int
     */
    public function saveEntry(Entry $entry, ?int $offset = null): int
    {
        if ($offset) {
            $resultOffset = $offset;
            $entry->setOffset($offset);
        } elseif ($entry->getOffset()) {
            $resultOffset = $entry->getOffset();
        } else {
            throw new RuntimeException('entry offset location not found');
        }

        if ($offset + Entry::SIZE > $this->metaData->getTotalAllocatedBytes()) {
            throw new RuntimeException('memory limit exceeded');
        }

        $entryStr = pack("q*", $entry->getKey(), $entry->getValue(), $entry->getNext());

        return shmop_write($this->shm_id, $entryStr, $resultOffset);
    }

    /**
     * Хэш функция, так как по условию все ключи целочисленные, то просто берем остаток от деления на вычисляемое значение количества корзин
     *
     * @param int $key
     *
     * @return int
     */
    private function calcHash(int $key): int
    {
        return ($key % $this->metaData->getTotalBuckets()) * Bucket::SIZE + MetaData::SIZE + PHP_INT_SIZE;
    }
}