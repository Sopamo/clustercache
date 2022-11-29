<?php

namespace App\ClusterCache;

use App\ClusterCache\Drivers\MemoryDriverInterface;

class MetaInformation
{
    const RESERVED_KEY = 1;

    private array $data;

    /**
     * @param MemoryDriverInterface $memoryDriver
     */
    public function __construct(private MemoryDriverInterface $memoryDriver)
    {
    }

    private function fetchData() {
        $this->data = $this->memoryDriver->get(self::RESERVED_KEY);
    }

    public function getCacheEntryMetaInformation(string $key):array {
        return $this->data[$key];
    }

    public function getMemoryKey(string $cacheKey):mixed {
        return $this->data[$cacheKey]['memory_key'];
    }
}