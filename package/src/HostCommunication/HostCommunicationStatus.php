<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;

class HostCommunicationStatus
{
    public static function init():void {
        Host::updateOrCreate([
            'ip' => HostHelpers::getHostIp()
        ]);
        Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
        app(HostCommunication::class)->triggerAll(Event::fromInt(Event::$allEvents['FETCH_HOSTS']));
    }

    public static function leave():void {
        Host::where('ip', HostHelpers::getHostIp())->delete();
        Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
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