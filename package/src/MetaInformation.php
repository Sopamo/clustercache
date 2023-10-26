<?php

namespace Sopamo\ClusterCache;

use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;

class MetaInformation
{
    const RESERVED_KEY = 1;
    /**
     * 1 MB
     */
    const RESERVED_LENGTH_IN_BYTES = 1048576;
    private static MemoryDriverInterface $memoryDriver;

    public static function setMemoryDriver(MemoryDriverInterface $memoryDriver): void
    {
        self::$memoryDriver = $memoryDriver;
    }

    /**
     * @param  string  $key
     * @return null|array{memory_key: string|int, length: int, is_locked: bool, is_being_written: bool, updated_at: int, ttl: int}
     */
    public function get(string $key): mixed
    {
        $data = $this->getAll();
        //logger("data from MetaInformation for '$key'");
        //logger(json_encode($data));
        if (isset($data[$key])) {
            return $data[$key];
        }
        return null;
    }

    private function getAll(): array
    {
        $data = self::$memoryDriver->get(self::RESERVED_KEY, self::RESERVED_LENGTH_IN_BYTES);
        if (!$data) {
            return [];
        }
        return Serialization::unserialize($data);
    }

    public function delete(string $key): void
    {
        $data = $this->getAll();
        unset($data[$key]);
        self::$memoryDriver->put(self::RESERVED_KEY, Serialization::serialize($data), self::RESERVED_LENGTH_IN_BYTES);
    }

    /**
     * @param  string  $key
     * @param  array{memory_key: string|int, length: int, is_locked: bool, is_being_written: bool, updated_at: int, ttl: int}  $value
     * @return array
     */
    public function put(string $key, array $value): array
    {
        $data = $this->getAll();
        $data[$key] = $value;
        self::$memoryDriver->put(self::RESERVED_KEY, Serialization::serialize($data), self::RESERVED_LENGTH_IN_BYTES);

        return $data[$key];
    }
}
