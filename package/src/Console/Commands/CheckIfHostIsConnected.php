<?php

namespace Sopamo\ClusterCache\Console\Commands;

use Illuminate\Console\Command;
use Sopamo\ClusterCache\Exceptions\DisconnectedWithAtLeastHalfOfHostsException;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\HostInNetwork;
use Sopamo\ClusterCache\LocalCacheManager;
use Sopamo\ClusterCache\Models\Host;

class CheckIfHostIsConnected extends Command
{
    protected $signature = 'clustercache:checkifhotsisconnected';

    protected $description = 'Check if the host is connected';

    public function handle(): int
    {
        if(Host::where('ip', HostInNetwork::getHostIp())->exists()) {
            HostInNetwork::markAsConnected();
            return Command::SUCCESS;
        }

        try{
            app(HostCommunication::class)->triggerAll(Event::$allEvents['TEST_CONNECTION']);
            HostInNetwork::join();
            app(LocalCacheManager::class)->clear();
        } catch (DisconnectedWithAtLeastHalfOfHostsException) {
            HostInNetwork::markAsDisconnected();
        }

        return Command::SUCCESS;
    }

}