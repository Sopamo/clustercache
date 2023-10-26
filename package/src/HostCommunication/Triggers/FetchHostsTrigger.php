<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostHelpers;

class FetchHostsTrigger extends Trigger implements TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null, array $optionalData = []): bool
    {
        $url =  config('clustercache.protocol') . '://' . $ip . '/clustercache/api/fetch-hosts';

        if($this->executeRequest($url) !== HostHelpers::HOST_REQUEST_RESPONSE) {
            return false;
        }

        return true;
    }
}