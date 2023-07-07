<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

class CacheKeyUpdatingHasCancelledTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null): bool
    {
        // TODO

        return true;
    }
}