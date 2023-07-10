<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\TimeHelpers;

class DBLocker
{
    /**
     * Timeout in seconds
     */
    public static int $timeout = 5;

    public function acquire(string $key): void
    {
        $cacheEntry = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();

        if (!$cacheEntry) {
            $cacheEntry = new CacheEntry();
            $cacheEntry->key = $key;
            $cacheEntry->value = '';
        }

        $cacheEntry->locked_at = TimeHelpers::getNowFromDB();
        $cacheEntry->save();
    }

    public function release(string $key): void
    {
        $cacheEntry = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();

        if ($cacheEntry) {
            $cacheEntry->locked_at = null;
            $cacheEntry->save();
        }
    }

    /**
     * @param  string  $key
     * @param  int  $retryIntervalMilliseconds
     * @param  int  $attemptLimit
     * @return bool
     */
    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool
    {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;

        for ($i = 0; $i < $attemptLimit - 1; $i++) {
            $isLocked = CacheEntry::where('key', $key)->whereNotNull('locked_at')->exists();
            if (!$isLocked) {
                return false;
            }
            usleep($retryIntervalMicroseconds);
        }

        $cacheEntry = CacheEntry::select(['locked_at'])->where('key', $key)->first();

        if (!$cacheEntry) {
            return false;
        }
        if (!$cacheEntry->locked_at) {
            return false;
        }
        if ($cacheEntry->locked_at->addSeconds(self::$timeout + TimeHelpers::getTimeShift())->isPast()) {
            return false;
        }

        return true;
    }
}