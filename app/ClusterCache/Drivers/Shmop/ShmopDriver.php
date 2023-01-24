<?php

namespace App\ClusterCache\Drivers\Shmop;

use App\ClusterCache\Drivers\MemoryDriverInterface;

class ShmopDriver implements MemoryDriverInterface
{
    const METADATA_LENGTH = 40;

    public static function put(string $memoryKey, mixed $value): bool
    {
        // TODO: Implement put() method.
    }

    public static function get(string $memoryKey): mixed
    {
        // TODO: Implement get() method.
    }

    public static function delete(string $memoryKey): bool
    {
        // TODO: Implement delete() method.
    }

    public static function createOrOpenMemoryBlock(string $memoryKey, int $length, ShmopConnectionMode $mode): \Shmop
    {
        return shmop_open($memoryKey, $mode->value, 0644, $length);
    }

    private static function decimalToBinary(int $number, int $bits = 40): string {
        return sprintf("%0" . $bits . "b", $number);
    }
}