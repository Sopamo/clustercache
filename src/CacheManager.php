<?php

namespace Sopamo\ClusterCache;

use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\LockingMechanisms\EventLocker;
use Sopamo\ClusterCache\LockingMechanisms\MemoryBlockLocker;
use Sopamo\ClusterCache\Models\CacheEntry;
use Illuminate\Support\Carbon;

class CacheManager
{
    private MemoryDriverInterface $memoryDriver;
    private MetaInformation $metaInformation;


    public function __constuctor(MemoryDriver $memoryDriver):void
    {
        $this->metaInformation = app(MetaInformation::class, ['memoryDriver' => $memoryDriver]);
        $this->memoryDriver = $memoryDriver->driver;
    }

    public function put(string $key, mixed $value, int $ttl = 0):bool {
        if(EventLocker::isLocked($key)) {
            return false;
        }
        if(DBLocker::isLocked($key)) {
            return false;
        }

        DBLocker::acquire($key);
        HostCommunication::triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_IS_UPDATING']), $key);
        try{
            $cacheEntry = CacheEntry::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'ttl' => $ttl,
                ]
            );
            HostCommunication::triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
            self::putIntoLocalCache($cacheEntry);
            DBLocker::release($key);

            return true;
        } catch (CacheEntryValueIsOutOfMemoryException $e) {
            HostCommunication::triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_UPDATING_HAS_CANCELED']), $key);
            DBLocker::release($key);

            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null):mixed {
        if(EventLocker::isLocked($key)) {
            return $default;
        }

        try{
            $metaInformation = $this->metaInformation->get($key);
            if(!$metaInformation) {
                throw new NotFoundLocalCacheKeyException();
            }
            if(MemoryBlockLocker::isLocked($key)) {
                return $default;
            }

            $expiredAt = $metaInformation['updated_at'] + $metaInformation['ttl'];
            if($metaInformation['ttl'] && Carbon::now()->timestamp > $expiredAt) {
                self::delete($key);
                return $default;
            }

            $cachedValue = self::$memoryDriver->get($metaInformation['memory_key'], $metaInformation['length']);
            if(!$cachedValue) {
                throw new NotFoundLocalCacheKeyException();
            }
            $cachedValue = unserialize($cachedValue);
        } catch (NotFoundLocalCacheKeyException) {
            $cacheEntry = CacheEntry::where('key', $key)->first();

            if(!$cacheEntry) {
                $this->metaInformation->
delete($key);
                return $default;
            }

            self::putIntoLocalCache($cacheEntry);
            $cachedValue = $cacheEntry->value;
        }
        return $cachedValue;
    }

    /**
     * @param string $key
     * @return bool
     *@throws NotFoundLocalCacheKeyException
     */
    public function delete(string $key):bool {
        if(EventLocker::isLocked($key)) {
            return false;
        }
        if(DBLocker::isLocked($key)) {
            return false;
        }

        DBLocker::acquire($key);
        HostCommunication::triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_IS_UPDATING']), $key);
        CacheEntry::where('key', $key)->delete();
        $metaInformation = $this->metaInformation->
get($key);
        if($metaInformation) {
            self::$memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']);
        }
        $this->metaInformation->
delete($key);
        HostCommunication::triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
        DBLocker::release($key);

        return true;
    }

    private function putIntoLocalCache(CacheEntry $cacheEntry): void
    {
        $value = serialize($cacheEntry->value);
        $valueLength = strlen($value);

        $metaInformation = $this->metaInformation->
get($cacheEntry->key);
        if(!$metaInformation) {
            $memoryKey = self::$memoryDriver->generateMemoryKey();
            $metaInformation = [
                'memory_key' => $memoryKey,
                'is_locked' => false,
                'length' => $valueLength,
            ];
        }
        $metaInformation['is_being_written'] = true;

        if($valueLength > $metaInformation['length']) {
            // if the new length is greater than the old length,
            // the memory block has to be deleted and created again
            self::$memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']);
            $metaInformation['length'] = $valueLength;
        }

        $nowFromDB = Carbon::createFromFormat('Y-m-d H:i:s',  DBLocker::getNowFromDB());
        $metaInformation['updated_at'] = $cacheEntry->updated_at->timestamp + Carbon::now()->timestamp - $nowFromDB->timestamp;
        $metaInformation['ttl'] = $cacheEntry->ttl;
        $this->metaInformation->put($cacheEntry->key, $metaInformation);

        self::$memoryDriver->put($metaInformation['memory_key'], $value, $metaInformation['length']);

        $metaInformation['is_being_written'] = false;
        $this->metaInformation->put($cacheEntry->key, $metaInformation);
    }
}
