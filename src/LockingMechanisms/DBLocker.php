<?php

namespace Sopamo\ClusterCache\LockingMechanisms;

use Sopamo\ClusterCache\Models\CacheEntry;
use Illuminate\Support\Facades\DB;

class DBLocker
{
    public function acquire(string $key): void {
        $cacheKey = CacheEntry::select(['locked_at'])->where('key', $key)->first();
        if(!$cacheKey) {
            $cacheKey = new CacheEntry();
            $cacheKey->key = $key;
            $cacheKey->value = '';
        }
        $cacheKey->locked_at = $this->getNowFromDB();
        var_dump($cacheKey->locked_at);
        $cacheKey->save();
    }
    public function release(string $key): void {
        $cacheKey = CacheEntry::select(['locked_at'])->where('key', $key)->first();
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
     * @TODO Implements timeout for a lock
     */
    public function isLocked(string $key, int $retryIntervalMilliseconds = 200, int $attemptLimit = 3): bool {
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

    public function getNowFromDB():string {
        return DB::select(DB::raw('SELECT NOW() as now'))[0]->now;
    }
}