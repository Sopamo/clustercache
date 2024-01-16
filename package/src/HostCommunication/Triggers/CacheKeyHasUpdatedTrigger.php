<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostResponse;

class CacheKeyHasUpdatedTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null, array $optionalData = []): HostResponse
    {
        $url =  config('clustercache.protocol') . '://' . $ip . '/clustercache/api/call-event/' . $cacheKey . '/' . Event::$allEvents['CACHE_KEY_HAS_UPDATED'];

        logger('$url: ' . $url);
        logger('this->executeRequest($url): ' . $this->executeRequest($url));

        return new HostResponse($this->executeRequest($url));
    }
}