<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\Models\DisconnectedHost;
use Sopamo\ClusterCache\Models\Host;

class HostStatus
{
    public static function init():void {
        Host::updateOrCreate([
            'ip' => HostHelpers::getHostIp()
        ]);
        //logger('Putting cache in host ' . HostHelpers::getHostIp() . ': ' . Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip')));
        //Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
        //logger('Fetching hosts in init() in ' . HostHelpers::getHostIp() . ' from local storage');
        //logger(json_encode(Cache::store('clustercache')->get('clustercache_hosts')));

        app(HostCommunication::class)->triggerAll(Event::fromInt(Event::$allEvents['FETCH_HOSTS']));
    }

    public static function leave():void {
        Cache::store('clustercache')->put('clustercache_hosts', Host::where('ip', '!=', HostHelpers::getHostIp())->pluck('ip'));
        Host::where('ip', HostHelpers::getHostIp())->delete();
        DisconnectedHost::where('from', HostHelpers::getHostIp())->orWhere('to', HostHelpers::getHostIp())->delete();
        app(HostCommunication::class)->triggerAll(Event::fromInt(Event::$allEvents['FETCH_HOSTS']));
    }

    public static function testConnections():void {
        $hostCommunication =  app(HostCommunication::class);
        foreach ($hostCommunication->getHostIps() as $hostIp) {
            if ($hostIp === HostHelpers::getHostIp()) {
                continue;
            }

            logger("$hostIp: ");
            logger($hostCommunication->trigger(Event::fromInt(Event::$allEvents['TEST_CONNECTION']), $hostIp));
        }
    }
}