<?php

namespace Sopamo\ClusterCache;

class CacheKey
{
    const INTERNAL_USED_KEYS = [
        'hosts' => 'clustercache_hosts',
        'isConnected' => 'clustercache_isConnected',
    ];
}