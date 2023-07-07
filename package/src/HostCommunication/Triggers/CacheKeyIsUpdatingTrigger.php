<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostHelpers;

class CacheKeyIsUpdatingTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null): bool
    {
        // TODO

        return true;
    }
}