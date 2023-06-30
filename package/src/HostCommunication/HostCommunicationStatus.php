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
        Cache::store('clustercache')->put('clustercache_hosts', Host::all()->pluck('ip'));
    }

    public static function leave():void {
        Host::where('ip', HostHelpers::getHostIp())->delete();
        Cache::store('clustercache')->put('clustercache_hosts', Host::all()->pluck('ip'));
    }
}