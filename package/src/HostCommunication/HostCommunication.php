<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyHasUpdatedTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyIsUpdatingTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyUpdatingHasCancelledTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\FetchHostsTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;
use UnhandledMatchError;

class HostCommunication
{
    public function triggerAll(Event $event, string $cacheKey = null): void
    {
        $hostIps = Cache::store('clustercache')->get('clustercache_hosts');
        if(!$hostIps) {
            $hostIps = Host::pluck('ip');
        }

        foreach ($hostIps as $hostIp) {
            if($hostIp === HostHelpers::getHostIp()) {
                continue;
            }

            $this->trigger($event, $hostIp, $cacheKey);
        }
    }

    public function trigger(Event $event, string $hostIp, string $cacheKey = null): void
    {
        $trigger = match($event->value) {
            Event::$allEvents['TEST_CONNECTION'] => new TestConnectionTrigger(),
            Event::$allEvents['FETCH_HOSTS'] => new FetchHostsTrigger(),
            Event::$allEvents['CACHE_KEY_IS_UPDATING'] => new CacheKeyIsUpdatingTrigger(),
            Event::$allEvents['CACHE_KEY_HAS_UPDATED'] => new CacheKeyHasUpdatedTrigger(),
            Event::$allEvents['CACHE_KEY_UPDATING_HAS_CANCELED'] => new CacheKeyUpdatingHasCancelledTrigger(),
            default => throw new UnhandledMatchError('The event does not exist'),
        };

        $trigger->handle($hostIp, $cacheKey);
    }
}