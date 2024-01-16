<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostResponse;

class InformHostCacheKeyHasUpdatedTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null, array $optionalData = []): HostResponse
    {
        $url =  config('clustercache.protocol') . '://' . $ip . '/clustercache/api/call-event/' . $cacheKey . '/' . Event::$allEvents['INFORM_HOST_CACHE_KEY_HAS_UPDATED'] . '/' . $optionalData['hostToInform'];

        return new HostResponse($this->executeRequest($url));
    }
}