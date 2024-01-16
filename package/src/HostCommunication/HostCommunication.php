<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Sopamo\ClusterCache\CachedHosts;
use Sopamo\ClusterCache\Exceptions\DisconnectedWithAtLeastHalfOfHostsException;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyHasUpdatedTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\FetchHostsTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionToHostTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;
use Sopamo\ClusterCache\HostInNetwork;
use Sopamo\ClusterCache\Models\DisconnectedHost;
use Sopamo\ClusterCache\Models\Host;
use UnhandledMatchError;

class HostCommunication
{
    /**
     * @throws DisconnectedWithAtLeastHalfOfHostsException
     */
    public function triggerAll(Event $event, string $cacheKey = null): void
    {
        $disconnectedHostCount = 0;

        foreach (CachedHosts::get() as $hostIp) {
            if($hostIp === HostInNetwork::getHostIp()) {
                continue;
            }

            logger(" Event: '$event->value', from: " . HostInNetwork::getHostIp() .", to: $hostIp");

            $triggerSuccessfully = $this->trigger($event, $hostIp, $cacheKey);

            if(!$triggerSuccessfully) {
                $disconnectedHostCount++;
            }

            logger("Trigger result: $triggerSuccessfully");

            // TODO I'll decide later what to do with that code. I might use it in next steps
//            if(!$triggerSuccessfully) {
//                $this->testConnectionFromEchHostToTargetHost($hostIp);
//            }
        }

        // we want to just inform all connected hosts about refreshing host state. It should be silent
        if($event->value === Event::$allEvents['FETCH_HOSTS']) {
            return;
        }

        if($disconnectedHostCount > Host::where('ip', '!=', HostInNetwork::getHostIp())->count() / 2) {
            throw new DisconnectedWithAtLeastHalfOfHostsException("The host is disconnected with $disconnectedHostCount hosts");
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

        // TODO I'll decide later what to do with that code. I might use it in next steps
//        if($triggerSuccessfully) {
//            $this->unmarkConnectionAsDisconnected(HostHelpers::getHostIp(), $hostIp);
//        } else {
//            $this->markConnectionAsDisconnected(HostHelpers::getHostIp(), $hostIp);
//        }

        return $triggerSuccessfully;
    }

    protected function testConnectionFromEchHostToTargetHost(string $targetHostIp):void {
        foreach (CachedHosts::get() as $hostIp) {
            if ($hostIp === HostInNetwork::getHostIp() || $hostIp === $targetHostIp) {
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
        $allHostIps = CachedHosts::get();
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