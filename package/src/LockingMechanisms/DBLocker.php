<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

use Illuminate\Support\Facades\DB;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\TimeHelpers;

class DBLocker
{
    /**
     * Timeout in seconds
     */
    public static int $timeout = 30;

    public function acquire(string $key): void {
        logger('DB connection ' . DB::connection()->getDatabaseName());
        logger('DbLocker: before select ' . microtime(true));
        $cacheEntry = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();
        logger('CacheEntry::select');
        logger(json_encode($cacheEntry));
        if(!$cacheEntry) {
            logger('DbLocker: before create CacheEntry ' . microtime(true));
            $cacheEntry = new CacheEntry();
            $cacheEntry->key = $key;
            $cacheEntry->value = '';
        }
        logger('DbLocker: before getNowFromDB ' . microtime(true));
        $cacheEntry->locked_at = TimeHelpers::getNowFromDB();
        logger('$cacheKey ' . $key);
        logger(json_encode($cacheEntry));
        logger('DbLocker: before save ' . microtime(true));
        $cacheEntry->save();
        logger('DbLocker: after save ' . microtime(true));
    }
    public function release(string $key): void {
        $cacheEntry = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();
        if($cacheEntry) {
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
    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;
        for($i = 0; $i < $attemptLimit-1; $i++) {
            $isLocked = CacheEntry::where('key', $key)->whereNotNull('locked_at')->exists();
            if(!$isLocked) {
                return false;
            }
            usleep($retryIntervalMicroseconds);
        }

        $cacheEntry = CacheEntry::select(['locked_at'])->where('key', $key)->first();
        if(!$cacheEntry->locked_at) {
            return false;
        }
        if($cacheEntry->locked_at->addSeconds(self::$timeout + TimeHelpers::getTimeShift())->isPast()) {
            return false;
        }

        return true;
    }
}