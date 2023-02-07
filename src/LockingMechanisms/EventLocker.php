<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

class EventLocker
{
    public function acquire(string $key): void {
        // TO DOO
    }
    public function release(string $key): void {
        // TO DOO
    }
    public function isLocked(string $key, int $waitByMMilliseconds = 200): bool {
        // TO DOO
        return false;
    }
}