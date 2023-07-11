<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyHasUpdatedTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\FetchHostsTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;
use UnhandledMatchError;

class HostCommunication
{
    public function triggerAll(Event $event, string $cacheKey = null): void
    {
        logger('in HostCommunication::triggerAll()');
        logger('before Cache::store()->get()');
        $hostIps = Cache::store('clustercache')->get('clustercache_hosts');
        logger('after Cache::store()->get()');
        if(!$hostIps) {
            $hostIps = Host::pluck('ip');
        }

        foreach ($hostIps as $hostIp) {
            logger('in loop ' . $hostIp);
            logger('$hostIp === HostHelpers::getHostIp() ' . $hostIp === HostHelpers::getHostIp());
            if($hostIp === HostHelpers::getHostIp()) {
                continue;
            }

            logger('before $this->trigger($event, $hostIp, $cacheKey). Event: ' . json_encode($event));
            $this->trigger($event, $hostIp, $cacheKey);
            logger('after $this->trigger($event, $hostIp, $cacheKey). Event: ' . json_encode($event));
        }
    }

    public function trigger(Event $event, string $hostIp, string $cacheKey = null): void
    {
        logger('before match() in trigger()');
        $trigger = match($event->value) {
            Event::$allEvents['TEST_CONNECTION'] => new TestConnectionTrigger(),
            Event::$allEvents['FETCH_HOSTS'] => new FetchHostsTrigger(),
            Event::$allEvents['CACHE_KEY_HAS_UPDATED'] => new CacheKeyHasUpdatedTrigger(),
            default => throw new UnhandledMatchError('The event does not exist'),
        };

        logger('before $trigger->handle($hostIp, $cacheKey). Trigger: ' . get_class($trigger));
        $trigger->handle($hostIp, $cacheKey);
        logger('after $trigger->handle($hostIp, $cacheKey)');
    }
}