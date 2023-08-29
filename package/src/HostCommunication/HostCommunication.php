<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyHasUpdatedTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\FetchHostsTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionToHostTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\DisconnectedHost;
use Sopamo\ClusterCache\Models\Host;
use UnhandledMatchError;

class HostCommunication
{
    public function triggerAll(Event $event, string $cacheKey = null): void
    {
        $disconnectedHostIps = [];

        foreach ($this->getHostIps() as $hostIp) {
            if($hostIp === HostHelpers::getHostIp()) {
                continue;
            }

            logger(" Event: '$event->value', from: " . HostHelpers::getHostIp() .", to: $hostIp");

            $trigger = $this->trigger($event, $hostIp, $cacheKey);

            logger("Trigger result: $trigger");

            if(!$trigger) {
                $this->markConnectionAsDisconnected(HostHelpers::getHostIp(), $hostIp);
                $this->testConnectionFromEchHostToTheHost($hostIp);
            }
        }
    }

    public function trigger(Event $event, string $hostIp, string $cacheKey = null, array $optionalData = []): bool
    {
        $trigger = match($event->value) {
            Event::$allEvents['TEST_CONNECTION'] => new TestConnectionTrigger(),
            Event::$allEvents['TEST_CONNECTION_TO_HOST'] => new TestConnectionToHostTrigger(),
            Event::$allEvents['FETCH_HOSTS'] => new FetchHostsTrigger(),
            Event::$allEvents['CACHE_KEY_HAS_UPDATED'] => new CacheKeyHasUpdatedTrigger(),
            default => throw new UnhandledMatchError('The event does not exist'),
        };

        return $trigger->handle($hostIp, $cacheKey, $optionalData);
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

    protected function testConnectionFromEchHostToTheHost(string $hostIpToTest):void {
        foreach ($this->getHostIps() as $hostIp) {
            if ($hostIp === HostHelpers::getHostIp() || $hostIp === $hostIpToTest) {
                continue;
            }

            logger(' Trigger Test Connection in host: ' . $hostIp);

            $trigger = $this->trigger(Event::fromInt(Event::$allEvents['TEST_CONNECTION_TO_HOST']), $hostIp, null, ['hostIp' => $hostIpToTest]);

            logger("Trigger test result: $trigger");
        }
        logger('trigger testConnection to all!');
    }
}