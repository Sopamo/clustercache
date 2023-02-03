<?php

namespace App\ClusterCache\Drivers;

interface MemoryDriverInterface
{
    public static function put(string $memoryKey, mixed $value, int $length):bool;
    public static function get(string $memoryKey, int $length):mixed;
    public static function delete(string $memoryKey, int $length):bool;
    public static function generateMemoryKey():int;
}