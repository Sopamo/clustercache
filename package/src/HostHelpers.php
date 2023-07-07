<?php

namespace Sopamo\ClusterCache;

class HostHelpers
{
    const HOST_REQUEST_RESPONSE = 'ok';

    public static function getHostIp():string {
        $ips = exec('hostname -i');

        if(!$ips) {
            return '';
        }

        return explode(' ', $ips)[0];
    }
}