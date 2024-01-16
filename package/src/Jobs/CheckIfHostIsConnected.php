<?php

namespace Sopamo\ClusterCache\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Sopamo\ClusterCache\Exceptions\DisconnectedWithAtLeastHalfOfHostsException;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\HostInNetwork;
use Sopamo\ClusterCache\LocalCacheManager;
use Sopamo\ClusterCache\Models\Host;

class CheckIfHostIsConnected
{
    use Dispatchable;
    public function __construct(public string $cacheKey)
    {

    }
    public function handle(): void
    {
        if(Host::where('ip', HostInNetwork::getHostIp())->exists()) {
            HostInNetwork::markAsConnected();
            return;
        }

        try{
            app(HostCommunication::class)->triggerAll(Event::$allEvents['TEST_CONNECTION']);
            HostInNetwork::join();
            app(LocalCacheManager::class)->clear();
        } catch (DisconnectedWithAtLeastHalfOfHostsException) {
            HostInNetwork::markAsDisconnected();
        }
    }
}