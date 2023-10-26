<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Psr\Container\NotFoundExceptionInterface;
use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\LockingMechanisms\MemoryBlockLocker;
use Sopamo\ClusterCache\Models\CacheEntry;

class CacheManager
{
    private MemoryDriverInterface $memoryDriver;
    private MetaInformation $metaInformation;
    private DBLocker $dbLocker;
    private MemoryBlockLocker $memoryBlockLocker;
    private HostCommunication $hostCommunication;
    private array $missBroadcastingForSpecialCacheKeys;


    public function __construct(MemoryDriver $memoryDriver)
    {
        MetaInformation::setMemoryDriver($memoryDriver->driver);
        $this->metaInformation = app(MetaInformation::class);
        $this->memoryDriver = $memoryDriver->driver;
        $this->dbLocker = app(DBLocker::class);
        $this->memoryBlockLocker = app(MemoryBlockLocker::class);
        $this->hostCommunication = app(HostCommunication::class);

        $this->missBroadcastingForSpecialCacheKeys = [config('clustercache.prefix') . ':clustercache_hosts'];
    }

    public function put(string $key, mixed $value, int $ttl = 0): bool
    {
        if ($this->dbLocker->isLocked($key)) {
            return false;
        }

        $this->dbLocker->acquire($key);

        try {
            $cacheEntry = CacheEntry::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'ttl' => $ttl,
                ]
            );

            //logger("Key: $key");
            //logger("missBroadcastingForSpecialCacheKeys: " . json_encode($this->missBroadcastingForSpecialCacheKeys));
            // It doesn't make sense to broadcast events like updating the internal cached data - for example "clustercache_hosts"
            if(!in_array($key, $this->missBroadcastingForSpecialCacheKeys)) {
                $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
            }

            $this->deleteFromLocalCache($cacheEntry->key);

            if(!$this->putIntoLocalCache($cacheEntry)) {
                // If something is wrong while putting into the local cache, the cache entry should be deleted from the local cache.
                // Our main goal is keeping of consistent data
                $this->deleteFromLocalCache($cacheEntry->key);
            }

            $this->dbLocker->release($key);

            //logger("putting successfully");
            return true;
        } catch (CacheEntryValueIsOutOfMemoryException $e) {
            logger($e->getMessage());
            $this->dbLocker->release($key);

            return false;
        }
    }

    private function putIntoLocalCache(CacheEntry $cacheEntry): bool
    {
        $value = Serialization::serialize($cacheEntry->value);
        $valueLength = strlen($value);

        $metaInformation = $this->metaInformation->get($cacheEntry->key);
        //logger('$metaInformation');
        //logger(json_encode($metaInformation));
        if (!$metaInformation) {
            $metaInformation = [
                'memory_key' => $this->memoryDriver->generateMemoryKey(),
                'is_locked' => false,
                'length' => $valueLength,
            ];
        }

        $metaInformation['updated_at'] = $cacheEntry->updated_at->getTimestamp() + TimeHelpers::getTimeShift();
        $metaInformation['ttl'] = $cacheEntry->ttl;

        $metaInformation['is_being_written'] = true;

        $this->metaInformation->put($cacheEntry->key, $metaInformation);

        $updatedMetaInformation = [...$metaInformation];

        //logger('$cacheEntry->value');
        //logger(json_encode($cacheEntry->value));
        //logger('$valueLength: ' . $valueLength);
        //logger('$metaInformation[\'length\']: ' . $metaInformation['length']);

        if ($valueLength > $metaInformation['length']) {
            // if the new length is greater than the old length,
            // the memory block has to be deleted and created again
            //logger("Delete '$cacheEntry->key': " . $this->memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']));
            $updatedMetaInformation['length'] = $valueLength;
            $updatedMetaInformation['memory_key'] = $this->memoryDriver->generateMemoryKey();
        }

        $isPut = $this->memoryDriver->put($metaInformation['memory_key'], $value, $metaInformation['length']);

        //logger("Putting '$cacheEntry->key' into local cache: " . $this->memoryDriver->put($metaInformation['memory_key'], $value, $metaInformation['length']));

        $updatedMetaInformation['is_being_written'] = false;

        if($isPut) {
            $this->metaInformation->put($cacheEntry->key, $updatedMetaInformation);
        } else {
            $this->metaInformation->put(
                $cacheEntry->key,
                [
                    ...$metaInformation,
                    'is_being_written' => false,
                ]
            );
        }

        return $isPut;
    }

    /**
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $metaInformation = $this->metaInformation->get($key);
            if (!$metaInformation) {
                throw new NotFoundLocalCacheKeyException();
            }

            if ($this->memoryBlockLocker->isLocked($key)) {
                logger('memoryBlockLocker->isLocked');
                return $default;
            }

            $expiredAt = $metaInformation['updated_at'] + $metaInformation['ttl'];
            if ($metaInformation['ttl'] && Carbon::now()->getTimestamp() > $expiredAt) {
                logger('cache is expired');
                $this->delete($key);
                return $default;
            }

            $cachedValue = $this->memoryDriver->get($metaInformation['memory_key'], $metaInformation['length']);
            if (!$cachedValue) {
                logger("$key is empty in local storage");
                throw new NotFoundLocalCacheKeyException();
            }
            $cachedValue = Serialization::unserialize($cachedValue);
            logger("$key comes from the local cache");
            logger(json_encode($cachedValue));
        } catch (NotFoundLocalCacheKeyException) {
            $cacheEntry = CacheEntry::where('key', $key)->first();

            if (!$cacheEntry) {
                logger(json_encode(CacheEntry::all()));
                logger(CacheEntry::getConnectionResolver()->getDefaultConnection());
                logger("$key does not exist in DB");
                $this->metaInformation->delete($key);
                return $default;
            }

            $this->putIntoLocalCache($cacheEntry);
            $cachedValue = $cacheEntry->value;
        }
        return $cachedValue;
    }

    /**
     * @param  string  $key
     * @return bool
     * @throws NotFoundExceptionInterface
     */
    public function delete(string $key): bool
    {
        if ($this->dbLocker->isLocked($key)) {
            return false;
        }

        $this->dbLocker->acquire($key);
        CacheEntry::where('key', $key)->delete();
        $metaInformation = $this->metaInformation->get($key);
        if ($metaInformation) {
            $this->memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']);
        }
        $this->metaInformation->delete($key);

        if(!in_array($key, $this->missBroadcastingForSpecialCacheKeys)) {
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
        }

        $this->dbLocker->release($key);

        return true;
    }

    public function deleteFromLocalCache(string $key): bool{
        $metaInformation = $this->metaInformation->get($key);
        if ($metaInformation) {
            $this->memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']);
        }
        $this->metaInformation->delete($key);

        return true;
    }
}
