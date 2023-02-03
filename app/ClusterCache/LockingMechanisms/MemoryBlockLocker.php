<?php

namespace App\ClusterCache\LockingMechanisms;

use App\ClusterCache\MetaInformation;

class MemoryBlockLocker
{
    /**
     * @param  string  $key
     * @param  int  $retryIntervalMilliseconds
     * @param  int  $attemptLimit
     * @return bool
     * @TODO Implements timeout for a lock
     */
    public static function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;
        for($i = 0; $i < $attemptLimit; $i++) {
            $metaInformation = MetaInformation::get($key);
            if(!$metaInformation || !$metaInformation['is_being_written']) {
                return false;
            }
            usleep($retryIntervalMicroseconds);
        }
        return true;
    }
}