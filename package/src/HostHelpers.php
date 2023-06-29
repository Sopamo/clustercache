<?php

namespace Sopamo\ClusterCache;

class HostHelpers
{
    public static function getHostIp():string {
        $ips = exec('hostname -i');

        if(!$ips) {
            return '';
        }

        return explode(' ', $ips)[0];
    }
}