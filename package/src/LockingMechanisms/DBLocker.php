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
        logger('DbLocker: before select ' . microtime(true));
        $cacheKey = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();
        logger('CacheEntry::select');
        logger(json_encode($cacheKey));
        if(!$cacheKey) {
            logger('DbLocker: before create CacheEntry ' . microtime(true));
            $cacheKey = new CacheEntry();
            $cacheKey->key = $key;
            $cacheKey->value = 'zz';
        }
        logger('DbLocker: before getNowFromDB ' . microtime(true));
        $cacheKey->locked_at = TimeHelpers::getNowFromDB();
        logger('DbLocker: before save ' . microtime(true));
        logger('$cacheKey ' . $key);
        logger(json_encode($cacheKey));
        logger('DB connection ' . DB::connection()->getDatabaseName());
        $cacheKey->save();
        logger('DbLocker: after save ' . microtime(true));
    }
    public function release(string $key): void {
        logger('DbLocker: before select in release ' . microtime(true));
        $cacheKey = CacheEntry::select(['id', 'locked_at'])->where('key', $key)->first();
        logger($key);
        logger(json_encode($cacheKey));
        if($cacheKey) {
            $cacheKey->locked_at = null;
            logger('DbLocker: before save in release ' . microtime(true));
            logger(json_encode($cacheKey));
            $cacheKey->save();
            logger('DbLocker: after save in release ' . microtime(true));
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