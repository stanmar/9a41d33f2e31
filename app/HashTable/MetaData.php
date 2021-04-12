<?php

namespace App\HashTable;

use JsonException;
use RuntimeException;
use Shmop;

/**
 * Метаданные таблицы. Сохраняется как json кодированный массив данных. (не эффективно, но просто, для демонстрации)
 */
class MetaData
{
    /**
     * Начнем сохранять данные со второго байта, в на первый будем инициализировать пустые значение
     */
    public const START_POSITION = PHP_INT_SIZE;

    /**
     * Размер метаданных в байтах
     */
    public const SIZE = 1024;

    public const KEY_TOTAL_BUCKETS = 'tb';
    public const KEY_LAST_ADDED_ENTRY_POSITION = 'laep';
    public const KEY_TOTAL_ALLOCATED_BYTES = 'tab';

    /**
     * Максимальное количество корзин
     *
     * @var int|mixed
     */
    private int $totalBuckets;

    /**
     * Позиция последней добавленной записи
     *
     * @var int|mixed
     */
    private int $lastAddedEntryPosition;

    /**
     * Сколько всего выделено памяти
     *
     * @var int
     */
    private int $totalAllocatedBytes;

    /**
     * @var Shmop
     */
    private Shmop $shm_id;

    /**
     * @param Shmop $shm_id
     * @param array $metadata
     */
    public function __construct(Shmop $shm_id, array $metadata)
    {
        $this->shm_id = $shm_id;
        $this->totalBuckets = $metadata[self::KEY_TOTAL_BUCKETS];
        $this->lastAddedEntryPosition = $metadata[self::KEY_LAST_ADDED_ENTRY_POSITION];
        $this->totalAllocatedBytes = $metadata[self::KEY_TOTAL_ALLOCATED_BYTES];
    }

    /**
     * Преобразование в массив
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::KEY_TOTAL_BUCKETS => $this->totalBuckets,
            self::KEY_LAST_ADDED_ENTRY_POSITION => $this->lastAddedEntryPosition,
            self::KEY_TOTAL_ALLOCATED_BYTES => $this->totalAllocatedBytes,
        ];
    }

    /**
     * @return int
     */
    public function getTotalBuckets(): int
    {
        return $this->totalBuckets;
    }

    /**
     * @return int
     */
    public function getLastAddedEntryPosition(): int
    {
        return $this->lastAddedEntryPosition;
    }

    /**
     * @param int $lastAddedEntryPosition
     */
    public function setLastAddedEntryPosition(int $lastAddedEntryPosition): void
    {
        $this->lastAddedEntryPosition = $lastAddedEntryPosition;
    }

    /**
     * @return int
     */
    public function getTotalAllocatedBytes(): int
    {
        return $this->totalAllocatedBytes;
    }

    /**
     * Сохраняем метаданные в памяти (через json не оптимально, но для удобно демонстрации)
     */
    public function save(): void
    {
        try {
            $metaDataStr = json_encode($this->toArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('unable to parse json');
        }

        if (strlen($metaDataStr) > self::SIZE) {
            throw new RuntimeException('metadata is too long');
        }
        shmop_write($this->shm_id, $metaDataStr, self::START_POSITION);
    }

    /**
     * Инициализация метаданных при первом создании таблицы
     *
     * @param Shmop $shm_id
     * @param int $totalAllocatedBytes
     *
     * @return MetaData
     */
    public static function init(Shmop $shm_id, int $totalAllocatedBytes): MetaData
    {
        // Примерно оценим сколько памяти выделить на корзину(bucket), а сколько на записи(entry)

        // Посчитаем сколько записей в таблице может уместиться когда совсем нет коллизий. В этом случае будет минимальное количество записей, но максимальное корзин
        $maxBucketCount = (int)(floor($totalAllocatedBytes - self::SIZE) / (Entry::SIZE + Bucket::SIZE));

        // Для простоты возьмем просто в 2 раза меньше. (пробовал по другому и закопался по времени)
        $optimalBucketCount = (int)($maxBucketCount / 2);

        // Структура в памяти следующая: Сначала один пустой байт, затем фиксированный блок с метаданными, затем фиксированный блок с корзинами, и остальная память под записи.
        // Установим "указатели" на область памяти где была записана последняя новая корзина, и где была записана последняя запись
        return new self(
            $shm_id,
            [
                self::KEY_TOTAL_BUCKETS => $optimalBucketCount,
                self::KEY_LAST_ADDED_ENTRY_POSITION => self::SIZE + $optimalBucketCount * Bucket::SIZE,
                self::KEY_TOTAL_ALLOCATED_BYTES => $totalAllocatedBytes,
            ]
        );
    }

    /**
     * Попытка получить метаданные таблицы
     *
     * @param Shmop $shm_id
     *
     * @return MetaData|null
     */
    public static function load(Shmop $shm_id): ?MetaData
    {
        $metadataSrt = shmop_read($shm_id, self::START_POSITION, self::SIZE);
        try {
            $metadata = json_decode(trim($metadataSrt), true, 512, JSON_THROW_ON_ERROR);

            return new self($shm_id, $metadata);
        } catch (JsonException) {
            return null;
        }
    }
}