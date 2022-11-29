<?php

namespace App\ClusterCache;

use App\ClusterCache\Drivers\MemoryDriverInterface;

class MetaInformation
{
    const RESERVED_KEY = 1;

    /**
     * @var MetaInformation[]
     */
    private static array $data;
    private static MemoryDriverInterface $memoryDriver;

    public function __construct(private string $memoryKey, private int $length)
    {
    }

    public static function init(MemoryDriverInterface $memoryDriver)
    {
        self::$memoryDriver = $memoryDriver;
    }

    private static function fetchData(): void
    {
        // TODO: I think array_map drops the array keys
        self::$data = array_map(function ($data) {
            return new self($data['memory_key'], $data['length']);
        }, self::$memoryDriver->get(self::RESERVED_KEY));
    }

    /**
     * @param string $key
     * @return array{memory_key: string, length: int}
     */
    public static function getCacheEntryMetaInformation(string $key): array
    {
        return self::$memoryDriver->get(self::RESERVED_KEY)[$key];
    }

    public function getMemoryKey(): mixed
    {
        return $this->memoryKey;
    }
}
