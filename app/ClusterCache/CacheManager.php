<?php

namespace App\ClusterCache;

use App\ClusterCache\Drivers\MemoryDriverInterface;
use App\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use App\ClusterCache\HostCommunication\HostCommunication;
use App\ClusterCache\HostCommunication\Event;
use App\ClusterCache\LockingMechanisms\DBLocker;
use App\ClusterCache\LockingMechanisms\EventLocker;
use App\ClusterCache\Models\CacheEntry;
use Illuminate\Support\Carbon;

class CacheManager
{
    private static MemoryDriverInterface $memoryDriver;
    private static MetaInformation $metaInformation;

    public static function init(MemoryDriver $memoryDriver):void
    {
        self::$memoryDriver = $memoryDriver->driver;
        self::$metaInformation = new MetaInformation();
        self::$metaInformation::init(self::$memoryDriver);
    }

    public static function put(string $key, mixed $value, int $ttl = 0):bool {
        if(EventLocker::isLocked($key)) {
            return false;
        }
        if(DBLocker::isLocked($key, self::getNowFromDB())) {
            return false;
        }

        DBLocker::acquire($key);
        HostCommunication::triggerAll(Event::$allEvents['CACHE_KEY_IS_UPDATING'], $key);
        // TO DO: Implement putting into DB
        HostCommunication::triggerAll(Event::$allEvents['CACHE_KEY_HAS_UPDATED'], $key);
        self::putIntoLocalCache($cacheEntry, $ttl);
        DBLocker::release($key);

        return true;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null):mixed {
        if(EventLocker::isLocked($key)) {
            return false;
        }

        $metaInformation = self::$metaInformation->get($key);
        try{
            // TO DO wait if is_being_written is true
            $expiredAt = $metaInformation['updated_at'] + $metaInformation['ttl'] * 1000;
            if(Carbon::now()->timestamp > $expiredAt) {
                self::delete($key);
                return null;
            }
            $cachedValue = self::$memoryDriver->get($metaInformation['memory_key']);
            $cachedValue = unserialize($cachedValue);
        } catch (NotFoundLocalCacheKeyException) {
            $cacheEntry = CacheEntry::where('key', $key)->first();

            if(!$cacheEntry) {
                return $default;
            }

            self::putIntoLocalCache($cacheEntry, $metaInformation['ttl']);
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
        HostCommunication::triggerAll(Event::$allEvents['CACHE_KEY_IS_UPDATING'], $key);
        CacheEntry::where('key', $key)->delete();
        self::$memoryDriver->delete(self::$metaInformation->get($key)['memory_key']);
        self::$metaInformation::delete($key);
        HostCommunication::triggerAll(Event::$allEvents['CACHE_KEY_HAS_UPDATED'], $key);
        DBLocker::release($key);

        return true;
    }

    private static function getNowFromDB():int {
        // TO DO
        return 777;
    }

    private static function putIntoLocalCache(CacheEntry $cacheEntry, int $ttl): void
    {
        $value = serialize($cacheEntry->value);
        $valueLength = strlen($value);

        $metaInformation = self::$metaInformation->get($cacheEntry->key);
        if(!$metaInformation) {
            $memoryKey = self::$memoryDriver->createMemoryBlock($cacheEntry->key, $valueLength);
            $metaInformation = [
                'memory_key' => $memoryKey,
                'is_locked' => false,
            ];
        }
        $metaInformation['is_being_written'] = true;
        $metaInformation['length'] = $valueLength;
        $metaInformation['updated_at'] = $cacheEntry->updated_at + Carbon::now()->timestamp - self::getNowFromDB();
        $metaInformation['ttl'] = $ttl;
        self::$metaInformation->put($cacheEntry->key, $metaInformation);

        self::$memoryDriver->put($metaInformation['memory_key'], $value);

        $metaInformation['is_being_written'] = false;
        self::$metaInformation->put($cacheEntry->key, $metaInformation);
    }
}
