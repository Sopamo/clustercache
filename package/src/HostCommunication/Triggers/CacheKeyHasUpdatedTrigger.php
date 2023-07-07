<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

class CacheKeyHasUpdatedTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null): bool
    {
        // TODO

        return true;
    }
}