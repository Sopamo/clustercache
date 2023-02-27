<?php

namespace Sopamo\ClusterCache;

use Psr\Container\NotFoundExceptionInterface;
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
    private EventLocker $eventLocker;
    private DBLocker $dbLocker;
    private MemoryBlockLocker $memoryBlockLocker;
    private HostCommunication $hostCommunication;


    public function __construct(MemoryDriver $memoryDriver)
    {
        MetaInformation::setMemoryDriver($memoryDriver->driver);
        $this->metaInformation = app(MetaInformation::class);
        $this->memoryDriver = $memoryDriver->driver;
        $this->eventLocker = app(EventLocker::class);
        $this->dbLocker = app(DBLocker::class);
        $this->memoryBlockLocker = app(MemoryBlockLocker::class);
        $this->hostCommunication = app(HostCommunication::class);
    }

    public function put(string $key, mixed $value, int $ttl = 0):bool {
        logger('before eventLocker '. microtime(true));
        if($this->eventLocker->isLocked($key)) {
            return false;
        }
        logger('before dbLocker '. microtime(true));
        if($this->dbLocker->isLocked($key)) {
            return false;
        }

        logger('before DB acquire '. microtime(true));
        $this->dbLocker->acquire($key);
        var_dump('DB acquires');
        logger('before triggerAll '. microtime(true));
        $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_IS_UPDATING']), $key);
        try{
            logger('before updateOrCreate '. microtime(true));
            $cacheEntry = CacheEntry::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'ttl' => $ttl,
                ]
            );
            var_dump('added');
            logger('before triggerAll 2 '. microtime(true));
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
            logger('before putIntoLocalCache '. microtime(true));
            $this->putIntoLocalCache($cacheEntry);
            logger('before DB locker release '. microtime(true));
            $this->dbLocker->release($key);
            logger('after DB locker release '. microtime(true));

            return true;
        } catch (CacheEntryValueIsOutOfMemoryException $e) {
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_UPDATING_HAS_CANCELED']), $key);
            logger('before DB locker release in Exception'. microtime(true));
            $this->dbLocker->release($key);

            return false;
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null):mixed {
        if($this->eventLocker->isLocked($key)) {
            return $default;
        }

        try{
            $metaInformation = $this->metaInformation->get($key);
            if(!$metaInformation) {
                throw new NotFoundLocalCacheKeyException();
            }
            if($this->memoryBlockLocker->isLocked($key)) {
                return $default;
            }

            $expiredAt = $metaInformation['updated_at'] + $metaInformation['ttl'];
            if($metaInformation['ttl'] && Carbon::now()->timestamp > $expiredAt) {
                $this->delete($key);
                return $default;
            }

            $cachedValue = $this->memoryDriver->get($metaInformation['memory_key'], $metaInformation['length']);
            if(!$cachedValue) {
                throw new NotFoundLocalCacheKeyException();
            }
            $cachedValue = unserialize($cachedValue);
        } catch (NotFoundLocalCacheKeyException) {
            $cacheEntry = CacheEntry::where('key', $key)->first();

            if(!$cacheEntry) {
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
    public function delete(string $key):bool {
        logger('CacheManager: delete: before eventLocker->isLocked ' . microtime(true));
        if($this->eventLocker->isLocked($key)) {
            return false;
        }
        logger('CacheManager: delete: before dbLocker->isLocked ' . microtime(true));
        if($this->dbLocker->isLocked($key)) {
            return false;
        }

        logger('CacheManager: delete: before dbLocker->acquire ' . microtime(true));
        $this->dbLocker->acquire($key);
        logger('CacheManager: delete: before triggerAll ' . microtime(true));
        $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_IS_UPDATING']), $key);
        logger('CacheManager: delete: before CacheEntry->delete ' . microtime(true));
        CacheEntry::where('key', $key)->delete();
        logger('CacheManager: delete: before metaInformation->get ' . microtime(true));
        $metaInformation = $this->metaInformation->get($key);
        if($metaInformation) {
            $this->memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']);
        }
        logger('CacheManager: delete: before metaInformation->delete ' . microtime(true));
        $this->metaInformation->delete($key);
        logger('CacheManager: delete: before triggerAll ' . microtime(true));
        $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
        logger('CacheManager: delete: before dbLocker->release ' . microtime(true));
        $this->dbLocker->release($key);
        logger('CacheManager: delete: after dbLocker->release ' . microtime(true));

        return true;
    }

    private function putIntoLocalCache(CacheEntry $cacheEntry): void
    {
        $value = serialize($cacheEntry->value);
        $valueLength = strlen($value);

        $metaInformation = $this->metaInformation->get($cacheEntry->key);
        if(!$metaInformation) {
            $memoryKey = $this->memoryDriver->generateMemoryKey();
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
            $this->memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']);
            $metaInformation['length'] = $valueLength;
        }

        $metaInformation['updated_at'] = $cacheEntry->updated_at->timestamp + TimeHelpers::getTimeShift();
        $metaInformation['ttl'] = $cacheEntry->ttl;
        $this->metaInformation->put($cacheEntry->key, $metaInformation);

        $this->memoryDriver->put($metaInformation['memory_key'], $value, $metaInformation['length']);

        $metaInformation['is_being_written'] = false;
        $this->metaInformation->put($cacheEntry->key, $metaInformation);
    }
}
