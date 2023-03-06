<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Sopamo\ClusterCache\Models\Host;

class HostCommunication
{
    public function triggerAll(Event $event, string $cacheKey = null): void
    {
        $hosts = Host::all();

        foreach ($hosts as $host) {
            $this->trigger($event, $host, $cacheKey);
        }
    }

    public function trigger(Event $event, Host $host, string $cacheKey = null): void
    {
        // TO DO
    }
}