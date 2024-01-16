<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sopamo\ClusterCache\Exceptions\ExpiredLocalCacheKeyException;
use Sopamo\ClusterCache\Exceptions\MemoryBlockIsLockedException;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\LockingMechanisms\MemoryBlockLocker;

class LocalCacheManager
{
    private MetaInformation $metaInformation;
    public function __construct()
    {
        $this->metaInformation = app(MetaInformation::class);
    }

    /**
     * @throws NotFoundLocalCacheKeyException
     * @throws NotFoundExceptionInterface
     * @throws ExpiredLocalCacheKeyException
     * @throws ContainerExceptionInterface
     * @throws MemoryBlockIsLockedException
     */
    public function get(string $key): mixed
    {
        $memoryBlockLocker = app(MemoryBlockLocker::class);

        $metaInformation = $this->metaInformation->get($key);
        if (!$metaInformation) {
            throw new NotFoundLocalCacheKeyException();
        }

        if ($memoryBlockLocker->isLocked($key)) {
            logger('memoryBlockLocker->isLocked');
            throw new MemoryBlockIsLockedException("The memory block for '$key' is locked");
        }

        $expiredAt = $metaInformation['updated_at'] + $metaInformation['ttl'];
        if ($metaInformation['ttl'] && Carbon::now()->getTimestamp() > $expiredAt) {
            logger('cache is expired');
            throw new ExpiredLocalCacheKeyException("The '$key' cache is expired");
        }

        $cachedValue = SelectedMemoryDriver::$memoryDriver->driver->get($metaInformation['memory_key'], $metaInformation['length']);
        if (!$cachedValue) {
            logger("$key is empty in local storage");
            throw new NotFoundLocalCacheKeyException();
        }

        return Serialization::unserialize($cachedValue);
    }

    public function put(string $key, mixed $value, int $updatedAt, int $ttl = 0): bool {

        $value = Serialization::serialize($value);
        $valueLength = strlen($value);

        $metaInformation = $this->metaInformation->get($key);

        if (!$metaInformation) {
            $metaInformation = [
                'memory_key' => SelectedMemoryDriver::$memoryDriver->driver->generateMemoryKey(),
                'is_locked' => false,
                'length' => $valueLength,
            ];
        }

        $metaInformation['updated_at'] = $updatedAt + TimeHelpers::getTimeShift();
        $metaInformation['ttl'] = $ttl;

        $metaInformation['is_being_written'] = true;

        $this->metaInformation->put($key, $metaInformation);

        $updatedMetaInformation = [...$metaInformation];

        if ($valueLength > $metaInformation['length']) {
            // if the new length is greater than the old length,
            // the memory block has to be deleted and created again
            $updatedMetaInformation['length'] = $valueLength;
            $updatedMetaInformation['memory_key'] = SelectedMemoryDriver::$memoryDriver->driver->generateMemoryKey();
        }

        $isPut = SelectedMemoryDriver::$memoryDriver->driver->put($metaInformation['memory_key'], $value, $metaInformation['length']);

        $updatedMetaInformation['is_being_written'] = false;

        if($isPut) {
            $this->metaInformation->put($key, $updatedMetaInformation);
        } else {
            $this->metaInformation->put(
                $key,
                [
                    ...$metaInformation,
                    'is_being_written' => false,
                ]
            );
        }

        return $isPut;
    }

    public function delete(string $key): bool{
        $metaInformation = $this->metaInformation->get($key);
        if ($metaInformation) {
            SelectedMemoryDriver::$memoryDriver->driver->delete($metaInformation['memory_key'], $metaInformation['length']);
        }
        $this->metaInformation->delete($key);

        return true;
    }
    public function clear():bool {
        foreach ($this->metaInformation->getAllCacheKeys() as $key) {
            $this->delete($key);
        }

        return true;
    }
}