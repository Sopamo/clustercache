<?php

namespace App\ClusterCache;

use App\ClusterCache\Drivers\MemoryDriverInterface;

class MetaInformation
{
    const RESERVED_KEY = 1;
    /**
     * 10 MB
     */
    const RESERVED_LENGTH_IN_BYTES = 10485760;

    private static MemoryDriverInterface $memoryDriver;

    public static function init(MemoryDriverInterface $memoryDriver): void
    {
        self::$memoryDriver = $memoryDriver;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|array{memory_key: string, length: int, is_locked: bool, is_being_written: bool, updated_at: int, ttl: int}
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $data = self::getAll();
        if(isset($data[$key])) {
            return $data[$key];
        }
        return $default;
    }

    /**
     * @param string $key
     * @param array{memory_key: string, length: int, is_locked: bool, is_being_written: bool, updated_at: int, ttl: int} $value
     * @return array
     */
    public static function put(string $key, array $value): array {
        $data = self::getAll();
        $data[$key] = $value;
        self::$memoryDriver->put(self::RESERVED_KEY, serialize($data), self::RESERVED_LENGTH_IN_BYTES);

        return $data[$key];
    }

    public static function delete(string $key): void {
        $data = self::getAll();
        unset($data[$key]);
        self::$memoryDriver->put(self::RESERVED_KEY, serialize($data), self::RESERVED_LENGTH_IN_BYTES);
    }

    private static function getAll():array {
        $data = self::$memoryDriver->get(self::RESERVED_KEY, self::RESERVED_LENGTH_IN_BYTES);
        if(!$data) {
            return [];
        }
        return unserialize($data);

    }
}
