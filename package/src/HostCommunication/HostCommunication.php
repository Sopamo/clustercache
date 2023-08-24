<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyHasUpdatedTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\FetchHostsTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\DisconnectedHost;
use Sopamo\ClusterCache\Models\Host;
use UnhandledMatchError;

class HostCommunication
{
    public function triggerAll(Event $event, string $cacheKey = null): void
    {
        foreach ($this->getHostIps() as $hostIp) {
            if($hostIp === HostHelpers::getHostIp()) {
                continue;
            }

            logger(" Event: '$event->value', from: " . HostHelpers::getHostIp() .", to: $hostIp");

            $trigger = $this->trigger($event, $hostIp, $cacheKey);

            logger("Trigger result: $trigger");

            if(!$trigger) {
                $this->markConnectionAsDisconnected(HostHelpers::getHostIp(), $hostIp);
            }
        }
    }

    public function trigger(Event $event, string $hostIp, string $cacheKey = null): bool
    {
        $trigger = match($event->value) {
            Event::$allEvents['TEST_CONNECTION'] => new TestConnectionTrigger(),
            Event::$allEvents['FETCH_HOSTS'] => new FetchHostsTrigger(),
            Event::$allEvents['CACHE_KEY_HAS_UPDATED'] => new CacheKeyHasUpdatedTrigger(),
            default => throw new UnhandledMatchError('The event does not exist'),
        };

        return $trigger->handle($hostIp, $cacheKey);
    }

    public function getHostIps():array {
        $hostIps = Cache::store('clustercache')->get('clustercache_hosts');
        if(!$hostIps) {
            $hostIps = Host::pluck('ip');
        }

        if($hostIps instanceof Collection) {
            return $hostIps->toArray();
        }

        return $hostIps;
    }

    protected function markConnectionAsDisconnected(string $from, string $to):void {
         DisconnectedHost::updateOrCreate([
            'from' => $from,
            'to' => $to,
        ]);
    }
}