<?php

namespace Sopamo\ClusterCache;

use InvalidArgumentException;
use Sopamo\ClusterCache\Events\CacheGettingWasCalled;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;
use Sopamo\ClusterCache\Exceptions\DisconnectedWithAtLeastHalfOfHostsException;
use Sopamo\ClusterCache\Exceptions\ExpiredLocalCacheKeyException;
use Sopamo\ClusterCache\Exceptions\HostIsMarkedAsDisconnectedException;
use Sopamo\ClusterCache\Exceptions\MemoryBlockIsLockedException;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\Exceptions\PutCacheException;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\Jobs\CheckIfHostIsConnected;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Models\Host;

class CacheManager
{
    private LocalCacheManager $localCacheManager;
    private DBLocker $dbLocker;
    private HostCommunication $hostCommunication;

    public function __construct()
    {
        $this->localCacheManager = app(LocalCacheManager::class);
        $this->dbLocker = app(DBLocker::class);
        $this->hostCommunication = app(HostCommunication::class);
    }

    /**
     * @throws PutCacheException
     */
    public function put(string $key, mixed $value, int $ttl = 0): bool
    {
        if(in_array($key, CacheKey::INTERNAL_USED_KEYS)) {
            throw new InvalidArgumentException("The key '$key' is not allowed");
        }

        if(!Host::where('ip', HostInNetwork::getHostIp())->exists()) {
            throw new PutCacheException('Host is marked as disconnected');
        }

        try{
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['TEST_CONNECTION']), $key);

        } catch (DisconnectedWithAtLeastHalfOfHostsException) {
            HostInNetwork::leave();
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
     * @throws HostIsMarkedAsDisconnectedException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $getCachedValue = function () use ($default, $key) {
            if (!HostInNetwork::isConnected()) {
                if (config('clustercache.disconnected_mode') === 'db') {
                    /** @var CacheEntry|null $cacheEntry */
                    $cacheEntry = CacheEntry::where('key', $key)->first();

                    if (!$cacheEntry) {
                        $this->localCacheManager->delete($key);

                        return $default;
                    }

                    return $cacheEntry->isExpired() ? $default : $cacheEntry->value;
                } else {
                    CheckIfHostIsConnected::dispatchAfterResponse($key);
                    throw new HostIsMarkedAsDisconnectedException('The host '.HostInNetwork::getHostIp().' is marked as disconnected');
                }
            }
            try {
                return $this->localCacheManager->get($key);
            } catch (ExpiredLocalCacheKeyException) {
                $this->localCacheManager->delete($key);
                return $default;
            } catch (MemoryBlockIsLockedException) {
                return $default;
            } catch (NotFoundLocalCacheKeyException) {
                /** @var CacheEntry|null $cacheEntry */
                $cacheEntry = CacheEntry::where('key', $key)->first();

                if (!$cacheEntry) {
                    logger(json_encode(CacheEntry::all()));
                    logger(CacheEntry::getConnectionResolver()->getDefaultConnection());
                    logger("$key does not exist in DB");
                    $this->localCacheManager->delete($key);
                    return $default;
                }

                if ($cacheEntry->isExpired()) {
                    return $default;
                }

                $this->localCacheManager->put($cacheEntry->key, $cacheEntry->value,
                    $cacheEntry->updated_at->getTimestamp(), $cacheEntry->ttl);
                $cachedValue = $cacheEntry->value;
            }
            return $cachedValue;
        };

        $value = $getCachedValue();

        CheckIfHostIsConnected::dispatchAfterResponse($key);
        return $value;
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
