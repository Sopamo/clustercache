<?php

namespace Sopamo\ClusterCache;

use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;

class EventLockInformation
{
    const RESERVED_KEY = 2;
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
     * @return null|int
     */
    public function get(string $key): mixed
    {
        $data = $this->getAll();
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
     * @param  int  $eventType
     * @return int
     */
    public function put(string $key, int $eventType): int
    {
        $data = $this->getAll();
        $data[$key] = $eventType;
        self::$memoryDriver->put(self::RESERVED_KEY, Serialization::serialize($data), self::RESERVED_LENGTH_IN_BYTES);

        return $data[$key];
    }
}
