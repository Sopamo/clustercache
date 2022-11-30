<?php

namespace App\ClusterCache\LockingMechanisms;

class DBLocker
{
    public static function acquire(string $key): void {
        // TO DOO
    }
    public static function release(string $key): void {
        // TO DOO
    }
    public static function isLocked(string $key, int $called_at, int $retryIntervalMilliseconds = 200): bool {
        // TO DOO
        return false;
    }
}