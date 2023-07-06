<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\Models\Host;

interface Trigger
{
    public function handle(string $ip, string $cacheKey = null):bool;
}