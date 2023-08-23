<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Carbon;
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


    public function __construct(MemoryDriver $memoryDriver)
    {
        MetaInformation::setMemoryDriver($memoryDriver->driver);
        $this->metaInformation = app(MetaInformation::class);
        $this->memoryDriver = $memoryDriver->driver;
        $this->dbLocker = app(DBLocker::class);
        $this->memoryBlockLocker = app(MemoryBlockLocker::class);
        $this->hostCommunication = app(HostCommunication::class);
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
            $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
            $this->putIntoLocalCache($cacheEntry);
            $this->dbLocker->release($key);

            return true;
        } catch (CacheEntryValueIsOutOfMemoryException $e) {
            $this->dbLocker->release($key);

            return false;
        }
    }

    private function putIntoLocalCache(CacheEntry $cacheEntry): void
    {
        $value = Serialization::serialize($cacheEntry->value);
        $valueLength = strlen($value);

        $metaInformation = $this->metaInformation->get($cacheEntry->key);
        if (!$metaInformation) {
            $memoryKey = $this->memoryDriver->generateMemoryKey();
            $metaInformation = [
                'memory_key' => $memoryKey,
                'is_locked' => false,
                'length' => $valueLength,
            ];
        }
        $metaInformation['is_being_written'] = true;

        if ($valueLength > $metaInformation['length']) {
            // if the new length is greater than the old length,
            // the memory block has to be deleted and created again
            $this->memoryDriver->delete($metaInformation['memory_key'], $metaInformation['length']);
            $metaInformation['length'] = $valueLength;
        }

        $metaInformation['updated_at'] = $cacheEntry->updated_at->getTimestamp() + TimeHelpers::getTimeShift();
        $metaInformation['ttl'] = $cacheEntry->ttl;
        $this->metaInformation->put($cacheEntry->key, $metaInformation);

        $this->memoryDriver->put($metaInformation['memory_key'], $value, $metaInformation['length']);

        $metaInformation['is_being_written'] = false;
        $this->metaInformation->put($cacheEntry->key, $metaInformation);
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
                return $default;
            }

            $expiredAt = $metaInformation['updated_at'] + $metaInformation['ttl'];
            if ($metaInformation['ttl'] && Carbon::now()->getTimestamp() > $expiredAt) {
                $this->delete($key);
                return $default;
            }

            $cachedValue = $this->memoryDriver->get($metaInformation['memory_key'], $metaInformation['length']);
            if (!$cachedValue) {
                throw new NotFoundLocalCacheKeyException();
            }
            $cachedValue = Serialization::unserialize($cachedValue);
        } catch (NotFoundLocalCacheKeyException) {
            $cacheEntry = CacheEntry::where('key', $key)->first();

            if (!$cacheEntry) {
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
        $this->hostCommunication->triggerAll(Event::fromInt(Event::$allEvents['CACHE_KEY_HAS_UPDATED']), $key);
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
