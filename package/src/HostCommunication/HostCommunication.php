<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Sopamo\ClusterCache\CachedHosts;
use Sopamo\ClusterCache\CacheKey;
use Sopamo\ClusterCache\Exceptions\DisconnectedWithAtLeastHalfOfHostsException;
use Sopamo\ClusterCache\HostCommunication\Triggers\CacheKeyHasUpdatedTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\FetchHostsTrigger;
use Sopamo\ClusterCache\HostCommunication\Triggers\InformHostCacheKeyHasUpdatedTrigger;
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

            $triggerSuccessfully = $this->trigger($event, $hostIp, $cacheKey)->wasSuccessful();

            if(!$triggerSuccessfully) {
                $disconnectedHostCount++;
            }

            logger("Trigger result: $triggerSuccessfully");
        }

        // we want to just inform all connected hosts about refreshing host state. It should be silent
        if($event->value === Event::$allEvents['FETCH_HOSTS']) {
            return;
        }

        $this->removeDisconnectedHosts();

        if($disconnectedHostCount > Host::where('ip', '!=', HostInNetwork::getHostIp())->count() / 2) {
            throw new DisconnectedWithAtLeastHalfOfHostsException("The host is disconnected with $disconnectedHostCount hosts");
        }
    }

    public function trigger(Event $event, string $hostIp, string $cacheKey = null, array $optionalData = []): HostResponse
    {
        $trigger = match($event->value) {
            Event::$allEvents['TEST_CONNECTION'] => new TestConnectionTrigger(),
            Event::$allEvents['TEST_CONNECTION_TO_HOST'] => new TestConnectionToHostTrigger(),
            Event::$allEvents['FETCH_HOSTS'] => new FetchHostsTrigger(),
            Event::$allEvents['CACHE_KEY_HAS_UPDATED'] => new CacheKeyHasUpdatedTrigger(),
            Event::$allEvents['INFORM_HOST_CACHE_KEY_HAS_UPDATED'] => new InformHostCacheKeyHasUpdatedTrigger(),
            default => throw new UnhandledMatchError('The event does not exist'),
        };

        $response = $trigger->handle($hostIp, $cacheKey, $optionalData);

        if($event->value === Event::$allEvents['FETCH_HOSTS']) {
            return $response;
        }

        if($response->wasSuccessful()) {
            $this->unmarkConnectionAsDisconnected(HostInNetwork::getHostIp(), $hostIp);
        } else {
            $this->markConnectionAsDisconnected(HostInNetwork::getHostIp(), $hostIp);

            if($event->value === Event::$allEvents['CACHE_KEY_HAS_UPDATED']) {
                $this->tryToUseOtherHostsToNotify($cacheKey, $hostIp);
            }
        }

        return $response;
    }

    protected function tryToUseOtherHostsToNotify(string $cacheKey, string $hostToInform):void {
        $anyHostResponded = false;

        foreach (CachedHosts::get() as $hostIp) {
            if ($hostIp === HostInNetwork::getHostIp()) {
                continue;
            }

            $response = $this->trigger(Event::fromInt(Event::$allEvents['INFORM_HOST_CACHE_KEY_HAS_UPDATED']), $hostIp, $cacheKey, ['hostToInform' => $hostToInform]);

            if($response->wasSuccessful()) {
                $anyHostResponded = true;
            }
        }

        if(!$anyHostResponded) {
            // TODO:Implement the "no" path for the question "Could any host tell the source host that they tried to tell the target host about the update?"
        } else {
            $this->removeDisconnectedHosts();
        }

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
        Cache::store('clustercache')->put(CacheKey::INTERNAL_USED_KEYS['hosts'], Host::pluck('ip'));
    }
}