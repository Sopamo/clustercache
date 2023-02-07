<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

use Sopamo\ClusterCache\MetaInformation;

class MemoryBlockLocker
{

    private MetaInformation $metaInformation;

    public function __construct()
    {
        $this->metaInformation = app(MetaInformation::class);
    }

    /**
     * @param  string  $key
     * @param  int  $retryIntervalMilliseconds
     * @param  int  $attemptLimit
     * @return bool
     * @TODO Implements timeout for a lock
     */
    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;
        for($i = 0; $i < $attemptLimit; $i++) {
            $metaInformation = $this->metaInformation->get($key);
            if(!$metaInformation || !$metaInformation['is_being_written']) {
                return false;
            }
            usleep($retryIntervalMicroseconds);
        }
        return true;
    }
}