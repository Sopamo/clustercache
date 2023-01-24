<?php

namespace App\ClusterCache\LockingMechanisms;

use App\ClusterCache\Models\CacheEntry;
use Illuminate\Support\Facades\DB;

class DBLocker
{
    public static function acquire(string $key): void {
        $cacheKey = CacheEntry::where('key', $key)->first();
        if(!$cacheKey) {
            $cacheKey = new CacheEntry();
            $cacheKey->key = $key;
            $cacheKey->value = '';
        }
        $cacheKey->locked_at = self::getNowFromDB();
        $cacheKey->save();
    }
    public static function release(string $key): void {
        $cacheKey = CacheEntry::where('key', $key)->first();
        if($cacheKey) {
            $cacheKey->locked_at = null;
            $cacheKey->save();
        }
    }
    public static function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool {
        $retryIntervalMicroseconds = $retryIntervalMilliseconds * 1000;

        for($i = 0; $i < $attemptLimit; $i++) {
            $isLocked = CacheEntry::where('key', $key)->whereNotNull('locked_at')->exists();

            if(!$isLocked) {
                return false;
            }

            usleep($retryIntervalMicroseconds);
        }
        return true;
    }

    private static function getNowFromDB():string {
        return DB::select(DB::raw('SELECT NOW() as now'))[0]->now;
    }
}