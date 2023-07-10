<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

use Carbon\Carbon;
use Sopamo\ClusterCache\EventLockInformation;
use Sopamo\ClusterCache\TimeHelpers;

class EventLocker
{
    /**
     * Timeout in seconds
     */
    public static int $timeout = 5;
    protected EventLockInformation $eventLockInformation;
    public function __construct() {
        $this->eventLockInformation = app(EventLockInformation::class);
    }
    public function acquire(string $key, int $eventType): void
    {
        $lockedAt = Carbon::now()->getTimestamp();
        logger('before $this->eventLockInformation->put');
        $this->eventLockInformation->put($key, ['value' => $eventType, 'locked_at' => $lockedAt]);
        logger('after $this->eventLockInformation->put');
    }

    public function release(string $key): void
    {
        $this->eventLockInformation->delete($key);
    }

    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool
    {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;
        for ($i = 0; $i < $attemptLimit; $i++) {
            logger('>eventLockInformation->get($key) ');
            $eventLockInformation = $this->eventLockInformation->get($key);
            logger('$eventLockInformation ' . $eventLockInformation);
            if (!$eventLockInformation) {
                return false;
            }
            usleep($retryIntervalMicroseconds);
        }

        $eventLockInformation = $this->eventLockInformation->get($key);
        if (!$eventLockInformation) {
            return false;
        }
        $lockedAt = Carbon::createFromTimestamp($eventLockInformation['locked_at']);
        if ($lockedAt->addSeconds(self::$timeout)->isPast()) {
            return false;
        }

        return true;
    }
}