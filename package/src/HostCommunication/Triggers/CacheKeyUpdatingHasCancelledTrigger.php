<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostHelpers;

class CacheKeyUpdatingHasCancelledTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null): bool
    {
        $url =  config('clustercache.protocol') . '://' . $ip . '/clustercache/api/call-event/' . $cacheKey . '/' . Event::$allEvents['CACHE_KEY_UPDATING_HAS_CANCELED'];

        if($this->executeRequest($url) !== HostHelpers::HOST_REQUEST_RESPONSE) {
            return false;
        }

        return true;
    }
}