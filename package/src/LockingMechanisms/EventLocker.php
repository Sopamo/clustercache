<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

use Sopamo\ClusterCache\EventLockInformation;

class EventLocker
{
    protected EventLockInformation $eventLockInformation;
    public function __construct() {
        $this->eventLockInformation = app(EventLockInformation::class);
    }
    public function acquire(string $key, int $eventType): void
    {
        $this->eventLockInformation->put($key, $eventType);
    }

    public function release(string $key): void
    {
        $this->eventLockInformation->delete($key);
    }

    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool
    {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;
        for ($i = 0; $i < $attemptLimit; $i++) {
            $eventLockInformation = $this->eventLockInformation->get($key);
            if (!$eventLockInformation) {
                return false;
            }
            usleep($retryIntervalMicroseconds);
        }
        return true;
    }
}