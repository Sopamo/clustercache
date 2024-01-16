<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\HostCommunication\HostResponse;

interface TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null, array $optionalData = []):HostResponse;
}