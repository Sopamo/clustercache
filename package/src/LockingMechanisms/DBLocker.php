<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\TimeHelpers;

class DBLocker
{
    /**
     * Timeout in seconds
     */
    public static int $timeout = 30;

    public function acquire(string $key): void {
        $cacheKey = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();
        if(!$cacheKey) {
            $cacheKey = new CacheEntry();
            $cacheKey->key = $key;
            $cacheKey->value = '';
        }
        $cacheKey->locked_at = TimeHelpers::getNowFromDB();
        $cacheKey->save();
    }
    public function release(string $key): void {
        $cacheKey = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();
        if($cacheKey) {
            $cacheKey->locked_at = null;
            $cacheKey->save();
        }
    }

    /**
     * @param  string  $key
     * @param  int  $retryIntervalMilliseconds
     * @param  int  $attemptLimit
     * @return bool
     */
    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;
        for($i = 0; $i < $attemptLimit-1; $i++) {
            $isLocked = CacheEntry::where('key', $key)->whereNotNull('locked_at')->exists();
            if(!$isLocked) {
                return false;
            }
            usleep($retryIntervalMicroseconds);
        }

        $cacheEntry = CacheEntry::where('key', $key)->first();
        if(!$cacheEntry->locked_at) {
            return false;
        }
        if($cacheEntry->locked_at->addSeconds(self::$timeout + TimeHelpers::getTimeShift())->isPast()) {
            return false;
        }

        return true;
    }
}