<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostCommunication\HostResponse;

class TestConnectionToHostTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null, array $optionalData = []): HostResponse
    {
        $url =  config('clustercache.protocol') . '://' . $ip . '/clustercache/api/test-connection-to/' . $optionalData['hostIp'];

        return new HostResponse($this->executeRequest($url));
    }
}