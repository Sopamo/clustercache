<?php

namespace App\ClusterCache\Drivers;

interface MemoryDriverInterface
{
    public static function put(string $key, mixed $value):bool;
    public static function get(string $key):mixed;
    public static function delete(string $key):bool;
}