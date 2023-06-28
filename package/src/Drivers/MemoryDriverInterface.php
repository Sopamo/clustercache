<?php

namespace Sopamo\ClusterCache\Drivers;

interface MemoryDriverInterface
{
    public function put(string|int $memoryKey, mixed $value, int $length): bool;

    public function get(string|int $memoryKey, int $length): mixed;

    public function delete(string|int $memoryKey, int $length): bool;

    public function generateMemoryKey(): int;
}