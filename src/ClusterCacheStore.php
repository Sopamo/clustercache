<?php

namespace Sopamo\ClusterCache;

use Illuminate\Contracts\Cache\Store;
use Sopamo\ClusterCache\Exceptions\UnexpectedTypeException;

class ClusterCacheStore implements Store
{
    protected string $prefix = '';
    protected CacheManager $cacheManager;
    protected MetaInformation $metaInformation;

    public function __construct(MemoryDriver $memoryDriver, string $prefix = '')
    {
        $this->setPrefix($prefix);
        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => $memoryDriver]);
        $this->metaInformation = app(MetaInformation::class);
        MetaInformation::setMemoryDriver($memoryDriver->driver);
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = ! empty($prefix) ? $prefix.':' : '';
    }

    public function get($key)
    {
        return $this->cacheManager->get($this->prefix.$key);
    }

    public function many(array $keys): array
    {
        $cachedEntries = [];

        foreach ($keys as $key) {
            $cachedEntries[$key] = $this->get($key);
        }

        return $cachedEntries;
    }

    public function put($key, $value, $seconds = 0)
    {
        $this->cacheManager->put($this->prefix.$key, $value, $seconds);
    }

    public function putMany(array $values, $seconds)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $seconds);
        }
    }

    public function increment($key, $value = 1)
    {
        try{
            $cachedValue = $this->get($key);

            if(!is_numeric($cachedValue)) {
                throw new UnexpectedTypeException('The incremented value has to be numeric');
            }
        } catch (UnexpectedTypeException $e) {
            return false;
        }

        $finalValue = $cachedValue + $value;

        $this->cacheManager->put($key, $finalValue);

        return $finalValue;
    }

    public function decrement($key, $value = 1)
    {
        // TODO: Implement decrement() method.
    }

    public function forever($key, $value)
    {
        // TODO: Implement forever() method.
    }

    public function forget($key)
    {
        // TODO: Implement forget() method.
    }

    public function flush()
    {
        // TODO: Implement flush() method.
    }

    public function getPrefix()
    {
        // TODO: Implement getPrefix() method.
    }
}