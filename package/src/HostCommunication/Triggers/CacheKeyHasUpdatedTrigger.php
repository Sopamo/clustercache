<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostHelpers;

class CacheKeyHasUpdatedTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null, array $optionalData = []): bool
    {
        $url =  config('clustercache.protocol') . '://' . $ip . '/clustercache/api/call-event/' . $cacheKey . '/' . Event::$allEvents['CACHE_KEY_HAS_UPDATED'];

        logger('$url: ' . $url);
        logger('this->executeRequest($url): ' . $this->executeRequest($url));

        if($this->executeRequest($url) !== HostHelpers::HOST_REQUEST_RESPONSE) {
            return false;
        }

        return true;
    }
}