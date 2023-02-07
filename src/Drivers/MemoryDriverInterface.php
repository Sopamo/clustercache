<?php

namespace Sopamo\ClusterCache\Drivers;

interface MemoryDriverInterface
{
    public function put(string $memoryKey, mixed $value, int $length):bool;
    public function get(string $memoryKey, int $length):mixed;
    public function delete(string $memoryKey, int $length):bool;
    public function generateMemoryKey():int;
}