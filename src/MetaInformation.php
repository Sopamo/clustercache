<?php

namespace Sopamo\ClusterCache;

use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;

class MetaInformation
{
    const RESERVED_KEY = 1;
    /**
     * 10 MB
     */
    const RESERVED_LENGTH_IN_BYTES = 10485760;


    public function __construct(private readonly MemoryDriverInterface $memoryDriver)
    {
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|array{memory_key: string, length: int, is_locked: bool, is_being_written: bool, updated_at: int, ttl: int}
     */
    public function get(string $key, mixed $default = null): mixed
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
    public function put(string $key, array $value): array {
        $data = self::getAll();
        $data[$key] = $value;
        $this->memoryDriver->put(self::RESERVED_KEY, serialize($data), self::RESERVED_LENGTH_IN_BYTES);

        return $data[$key];
    }

    public function delete(string $key): void {
        $data = self::getAll();
        unset($data[$key]);
        $this->memoryDriver->put(self::RESERVED_KEY, serialize($data), self::RESERVED_LENGTH_IN_BYTES);
    }

    private function getAll():array {
        $data = $this->memoryDriver->get(self::RESERVED_KEY, self::RESERVED_LENGTH_IN_BYTES);
        if(!$data) {
            return [];
        }
        return unserialize($data);

    }
}
