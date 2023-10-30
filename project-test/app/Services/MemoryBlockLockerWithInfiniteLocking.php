<?php

namespace App\Services;

use Sopamo\ClusterCache\LockingMechanisms\MemoryBlockLocker;

class MemoryBlockLockerWithInfiniteLocking extends MemoryBlockLocker
{
    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool
    {
        return true;
    }
}