<?php

namespace App\ClusterCache\HostCommunication;

use App\ClusterCache\Models\Host;

class HostCommunication
{
    public static function trigger(Trigger $trigger, Host $host, string $cacheKey = null): void
    {
        // TO DO
    }
    public static function triggerAll(Trigger $trigger, string $cacheKey = null): void
    {
        $hosts = Host::all();

        foreach ($hosts as $host) {
            self::trigger($trigger, $host, $cacheKey);
        }
    }
}