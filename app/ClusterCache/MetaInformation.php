<?php

namespace App\ClusterCache;

use App\ClusterCache\Drivers\MemoryDriverInterface;

class MetaInformation
{
    const RESERVED_KEY = 1;

    private static MemoryDriverInterface $memoryDriver;

    public static function init(MemoryDriverInterface $memoryDriver): void
    {
        self::$memoryDriver = $memoryDriver;
    }

    /**
     * @param string $key
     * @return array{memory_key: string, length: int, is_locked: bool, is_being_written: bool, updated_at: int}
     */
    public static function getMeta(string $key): array
    {
        return unserialize(self::$memoryDriver->get(self::RESERVED_KEY))[$key];
    }

    /**
     * @param string $key
     * @param array{memory_key: string, length: int, is_locked: bool, is_being_written: bool, updated_at: int} $value
     * @return array
     */
    public static function putMeta(string $key, array $value): array {
        $data = self::$memoryDriver->get(self::RESERVED_KEY);
        $data[$key] = $value;
        self::$memoryDriver->put(self::RESERVED_KEY, serialize($data));

        return $data[$key];
    }

    public static function deleteMeta(string $key): void {
        $data = self::$memoryDriver->get(self::RESERVED_KEY);
        unset($data[$key]);
        self::$memoryDriver->put(self::RESERVED_KEY, serialize($data));

    }
}
