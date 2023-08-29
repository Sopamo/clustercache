<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\Models\Host;

interface TriggerInterface
{
    public function handle(string $ip, string $cacheKey = null, array $optionalData = []):bool;
}