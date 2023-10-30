<?php

namespace Sopamo\ClusterCache;

use InvalidArgumentException;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;
use Sopamo\ClusterCache\Exceptions\DisconnectedWithAtLeastHalfOfHostsException;
use Sopamo\ClusterCache\Exceptions\ExpiredLocalCacheKeyException;
use Sopamo\ClusterCache\Exceptions\MemoryBlockIsLockedException;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\Exceptions\PutCacheException;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Models\Host;

class CacheManager
{
    private LocalCacheManager $localCacheManager;
    private MetaInformation $metaInformation;
    private DBLocker $dbLocker;
    private HostCommunication $hostCommunication;

    public function __construct()
    {
        $this->metaInformation = app(MetaInformation::class);
        $this->localCacheManager = app(LocalCacheManager::class);
        $this->dbLocker = app(DBLocker::class);
        $this->hostCommunication = app(HostCommunication::class);
    }

    /**
     * @throws PutCacheException
     */
    public function put(string $key, mixed $value, int $ttl = 0): bool
    {
        if(in_array($key, CacheKey::NOT_ALLOWED_KEYS)) {
            throw new InvalidArgumentException("The key '$key' is not allowed");
        }

        if(!Host::where('ip', HostHelpers::getHostIp())->exists()) {
            throw new PutCacheException('Host is marked as disconnected');
        }

        try{
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['TEST_CONNECTION']), $key);

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
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);

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
            return $this->localCacheManager->get($key);
        } catch (ExpiredLocalCacheKeyException) {
            $this->delete($key);
            return $default;
        } catch (MemoryBlockIsLockedException) {
            return $default;
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
     */
    public function delete(string $key): bool
    {
        if ($this->dbLocker->isLocked($key)) {
            return false;
        }

        $this->dbLocker->acquire($key);

        CacheEntry::where('key', $key)->delete();

        $this->localCacheManager->delete($key);

        $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);

        $this->dbLocker->release($key);

        return true;
    }
}
