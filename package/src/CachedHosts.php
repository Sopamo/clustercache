<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Carbon;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\Models\Host;

class CachedHosts
{
    public static function get():array {
        /** @var LocalCacheManager $localCacheManager */
        $localCacheManager = app(LocalCacheManager::class);

        try {
            return $localCacheManager->get(CacheKey::INTERNAL_USED_KEYS['hosts']);
        } catch (NotFoundLocalCacheKeyException) {
            return self::updateCache();
        }
    }

    public static function refresh(): void
    {
        self::updateCache();
    }

    private static function updateCache():array {
        /** @var LocalCacheManager $localCacheManager */
        $localCacheManager = app(LocalCacheManager::class);

        $hosts = Host::pluck('ip')->toArray();
        $localCacheManager->put(CacheKey::INTERNAL_USED_KEYS['hosts'], $hosts, Carbon::now()->getTimestamp());

        return $hosts;
    }

}