<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

class EventLocker
{
    public static function acquire(string $key): void {
        // TO DOO
    }
    public static function release(string $key): void {
        // TO DOO
    }
    public static function isLocked(string $key, int $waitByMMilliseconds = 200): bool {
        // TO DOO
        return false;
    }
}