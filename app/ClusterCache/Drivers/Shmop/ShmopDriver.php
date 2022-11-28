<?php

namespace App\ClusterCache\Drivers\Shmop;

use App\ClusterCache\Drivers\MemoryDriverInterface;

class ShmopDriver implements MemoryDriverInterface
{

    public static function put(string $key, mixed $value): bool
    {
        // TODO: Implement put() method.
    }

    public static function get(string $key): mixed
    {
        // TODO: Implement get() method.
    }

    public static function delete(string $key): bool
    {
        // TODO: Implement delete() method.
    }
}