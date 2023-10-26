<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Carbon;
use Psr\Container\NotFoundExceptionInterface;
use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;
use Sopamo\ClusterCache\Exceptions\DisconnectedWithAtLeastHalfOfHostsException;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\Exceptions\PutCacheException;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\LockingMechanisms\MemoryBlockLocker;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Models\Host;

class CacheManager
{
    private LocalCacheManager $localCacheManager;
    private MetaInformation $metaInformation;
    private DBLocker $dbLocker;
    private MemoryBlockLocker $memoryBlockLocker;
    private HostCommunication $hostCommunication;
    private array $missBroadcastingForSpecialCacheKeys;


    public function __construct()
    {
        $this->metaInformation = app(MetaInformation::class);
        $this->localCacheManager = app(LocalCacheManager::class);
        $this->dbLocker = app(DBLocker::class);
        $this->memoryBlockLocker = app(MemoryBlockLocker::class);
        $this->hostCommunication = app(HostCommunication::class);

        $this->missBroadcastingForSpecialCacheKeys = [config('clustercache.prefix') . ':clustercache_hosts'];
    }

    /**
     * @throws PutCacheException
     */
    public function put(string $key, mixed $value, int $ttl = 0): bool
    {
        if(!Host::where('ip', HostHelpers::getHostIp())->exists()) {
            throw new PutCacheException('Host is marked as disconnected');
        }

        try{
            // It doesn't make sense to broadcast events like updating the internal cached data - for example "clustercache_hosts"
            if(!in_array($key, $this->missBroadcastingForSpecialCacheKeys)) {
                $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['TEST_CONNECTION']), $key);
            }
        } catch (DisconnectedWithAtLeastHalfOfHostsException) {
            HostStatus::leave();
            throw new PutCacheException('Host is disconnected with at least half of hosts');
        }

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

            logger("Key: $key");
            //logger("missBroadcastingForSpecialCacheKeys: " . json_encode($this->missBroadcastingForSpecialCacheKeys));
            // It doesn't make sense to broadcast events like updating the internal cached data - for example "clustercache_hosts"
            if(!in_array($key, $this->missBroadcastingForSpecialCacheKeys)) {
                $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
            }

            $this->localCacheManager->delete($cacheEntry->key);

            if(!$this->localCacheManager->put($cacheEntry->key, $cacheEntry->value, $cacheEntry->updated_at->getTimestamp(), $cacheEntry->ttl)) {
                // If something is wrong while putting into the local cache, the cache entry should be deleted from the local cache.
                // Our main goal is keeping of consistent data
                $this->localCacheManager->delete($cacheEntry->key);
            }

            $this->dbLocker->release($key);

            logger("putting successfully");
            return true;
        } catch (CacheEntryValueIsOutOfMemoryException $e) {
            logger($e->getMessage());
            $this->dbLocker->release($key);

            throw new PutCacheException('Cache entry value is of out the memory');
        }
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

            $cachedValue = SelectedMemoryDriver::$memoryDriver->driver->get($metaInformation['memory_key'], $metaInformation['length']);
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

            $this->localCacheManager->put($cacheEntry->key, $cacheEntry->value, $cacheEntry->updated_at->getTimestamp(), $cacheEntry->ttl);
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
            SelectedMemoryDriver::$memoryDriver->driver->delete($metaInformation['memory_key'], $metaInformation['length']);
        }
        $this->metaInformation->delete($key);

        if(!in_array($key, $this->missBroadcastingForSpecialCacheKeys)) {
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
        }

        $this->dbLocker->release($key);

        return true;
    }
}
