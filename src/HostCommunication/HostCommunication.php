<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Sopamo\ClusterCache\Models\Host;

class HostCommunication
{
    public static function trigger(Event $event, Host $host, string $cacheKey = null): void
    {
        // TO DO
    }
    public static function triggerAll(Event $event, string $cacheKey = null): void
    {
        $hosts = Host::all();

        foreach ($hosts as $host) {
            self::trigger($event, $host, $cacheKey);
        }
    }
}