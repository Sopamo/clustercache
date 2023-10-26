<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        foreach ($this->getHostIps() as $hostIp) {
            if($hostIp === HostHelpers::getHostIp()) {
                continue;
            }

            logger(" Event: '$event->value', from: " . HostHelpers::getHostIp() .", to: $hostIp");

            $triggerSuccessfully = $this->trigger($event, $hostIp, $cacheKey);

            logger("Trigger result: $triggerSuccessfully");

            if(!$triggerSuccessfully) {
                $this->testConnectionFromEchHostToTargetHost($hostIp);
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

        $triggerSuccessfully =  $trigger->handle($hostIp, $cacheKey, $optionalData);

        if($triggerSuccessfully) {
            $this->unmarkConnectionAsDisconnected(HostHelpers::getHostIp(), $hostIp);
        } else {
            $this->markConnectionAsDisconnected(HostHelpers::getHostIp(), $hostIp);
        }

        return $triggerSuccessfully;
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

    protected function testConnectionFromEchHostToTargetHost(string $targetHostIp):void {
        foreach ($this->getHostIps() as $hostIp) {
            if ($hostIp === HostHelpers::getHostIp() || $hostIp === $targetHostIp) {
                continue;
            }

            logger(' Trigger Test Connection in host: ' . $hostIp);

            $trigger = $this->trigger(Event::fromInt(Event::$allEvents['TEST_CONNECTION_TO_HOST']), $hostIp, null, ['hostIp' => $targetHostIp]);

            logger("Trigger test result: $trigger");
        }
        logger('trigger testConnection to all!');
        $this->removeDisconnectedHosts();
    }

    protected function markConnectionAsDisconnected(string $from, string $to):void {
        DisconnectedHost::updateOrCreate([
            'from' => $from,
            'to' => $to,
        ]);
    }

    protected function unmarkConnectionAsDisconnected(string $from, string $to):void {
        logger("unmark Connection. From: $from, to: $to");
        DisconnectedHost::where('from', $from)
            ->where('to', $to)
            ->delete();
    }

    protected function removeDisconnectedHosts(): void {
        $allHostIps = $this->getHostIps();
        $hostCount = count($allHostIps);
        $disconnectedHosts = DisconnectedHost::select('to', DB::raw('COUNT(*) as count'))
            ->groupBy('to')
            ->get()
            ->keyBy('to');

        $hostsToRemove = [];

        foreach ($allHostIps as $hostIp) {
            $count = $disconnectedHosts->get($hostIp)?->count ?? 0;
            if($count / $hostCount > 0.5) {
                $hostsToRemove[] = $hostIp;
            }
        }

        Host::whereIn('ip', $hostsToRemove)->delete();
        DisconnectedHost::whereIn('from', $hostsToRemove)->orWhereIn('to', $hostsToRemove)->delete();
        Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
    }
}