<?php

namespace App\ClusterCache;

use App\ClusterCache\Drivers\MemoryDriverInterface;
use App\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use App\ClusterCache\HostCommunication\HostCommunication;
use App\ClusterCache\HostCommunication\Trigger;
use App\ClusterCache\Lockers\DBLocker;
use App\ClusterCache\Lockers\EventLocker;
use App\ClusterCache\Models\CacheEntry;

class CacheManager
{
    private static MemoryDriverInterface $memoryDriver;

    public static function init(MemoryDriver $memoryDriver):void
    {
        self::$memoryDriver = $memoryDriver->driver;
    }

    public static function put(string $key, mixed $value):bool {
        if(EventLocker::isLocked($key)) {
            return false;
        }
        if(DBLocker::isLocked($key, self::getNowFromDB())) {
            return false;
        }

        DBLocker::acquire($key);
        HostCommunication::triggerAll(Trigger::$allTriggers['CACHE_KEY_IS_UPDATING'], $key);
        // TO DO: Implement putting into DB
        HostCommunication::triggerAll(Trigger::$allTriggers['CACHE_KEY_HAS_UPDATED'], $key);
        self::$memoryDriver->put($key, $value);
        DBLocker::release($key);

        return true;
    }

    /**
     * @param string $key
     * @return mixed
     *@throws NotFoundLocalCacheKeyException
     */
    public static function get(string $key):mixed {
        if(EventLocker::isLocked($key)) {
            return false;
        }

        try{
            $cachedValue = self::$memoryDriver->get($key);
        } catch (NotFoundLocalCacheKeyException) {
            $cacheEntry = CacheEntry::where('key', $key)->first();
            self::$memoryDriver->put($cacheEntry->key, $cacheEntry->value);
            $cachedValue = $cacheEntry->value;
        }
        return $cachedValue;
    }

    /**
     * @param string $key
     * @return bool
     *@throws NotFoundLocalCacheKeyException
     */
    public static function delete(string $key):bool {
        if(EventLocker::isLocked($key)) {
            return false;
        }
        if(DBLocker::isLocked($key, self::getNowFromDB())) {
            return false;
        }

        DBLocker::acquire($key);
        HostCommunication::triggerAll(Trigger::$allTriggers['CACHE_KEY_IS_UPDATING'], $key);
        CacheEntry::where('key', $key)->delete();
        self::$memoryDriver->delete($key);
        HostCommunication::triggerAll(Trigger::$allTriggers['CACHE_KEY_HAS_UPDATED'], $key);
        DBLocker::release($key);

        return true;
    }

    private static function getNowFromDB():int {
        // TO DO
        return 777;
    }
}