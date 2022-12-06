<?php

namespace App\ClusterCache\Drivers;

interface MemoryDriverInterface
{
    public static function put(string $memoryKey, mixed $value):bool;
    public static function get(string $memoryKey):mixed;
    public static function delete(string $memoryKey):bool;
    public static function createMemoryBlock(string $memoryKey, int $length):int;
}