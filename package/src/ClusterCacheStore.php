<?php

namespace Sopamo\ClusterCache;

use Illuminate\Contracts\Cache\Store;
use Sopamo\ClusterCache\Exceptions\UnexpectedTypeException;
use Sopamo\ClusterCache\Models\CacheEntry;

class ClusterCacheStore implements Store
{
    protected string $prefix = '';
    protected CacheManager $cacheManager;
    protected MetaInformation $metaInformation;

    public function __construct(string $prefix = '')
    {
        $this->setPrefix($prefix);
        $this->cacheManager = app(CacheManager::class);
        $this->metaInformation = app(MetaInformation::class);
    }

    public function many(array $keys): array
    {
        $cachedEntries = [];

        foreach ($keys as $key) {
            $cachedEntries[$key] = $this->get($key);
        }

        return $cachedEntries;
    }

    public function get($key): mixed
    {
        if(is_array($key)) {
            $key = array_values($key)[0];
        }
        return $this->cacheManager->get($this->prefix.$key);
    }

    public function putMany(array $values, $seconds)
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $seconds);
        }

        return true;
    }

    public function put($key, $value, $seconds = 0): bool
    {
        return $this->cacheManager->put($this->prefix.$key, $value, $seconds);
    }

    public function increment($key, $value = 1): bool|int
    {
        try {
            $cachedValue = $this->get($key);

            if (!is_numeric($cachedValue)) {
                throw new UnexpectedTypeException('The incremented value has to be numeric');
            }
        } catch (UnexpectedTypeException $e) {
            return false;
        }

        $finalValue = $cachedValue + $value;

        $this->put($key, $finalValue);

        return $finalValue;
    }

    public function decrement($key, $value = 1): bool|int
    {
        try {
            $cachedValue = $this->get($key);

            if (!is_numeric($cachedValue)) {
                throw new UnexpectedTypeException('The incremented value has to be numeric');
            }
        } catch (UnexpectedTypeException $e) {
            return false;
        }

        $finalValue = $cachedValue - $value;

        $this->put($key, $finalValue);

        return $finalValue;
    }

    public function forever($key, $value): bool
    {
        return $this->put($key, $value);
    }

    public function forget($key): bool
    {
        return $this->cacheManager->delete($this->prefix.$key);
    }

    public function flush(): bool
    {
        $cacheEntries = CacheEntry::select('key')->get();

        foreach ($cacheEntries as $cacheEntry) {
            if (!$this->cacheManager->delete($cacheEntry->key)) {
                return false;
            }
        }

        return true;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = !empty($prefix) ? $prefix.':' : '';
    }
}