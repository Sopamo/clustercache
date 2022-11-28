<?php

namespace App\ClusterCache\Lockers;

class DBLocker
{
    public static function acquire(string $key): void {
        // TO DOO
    }
    public static function release(string $key): void {
        // TO DOO
    }
    public static function isLocked(string $key, int $called_at, int $waitByMMilliseconds = 200): bool {
        // TO DOO
        return false;
    }
}