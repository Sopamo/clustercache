<?php

namespace Sopamo\ClusterCache\HostCommunication;

use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;

class HostCommunicationStatus
{
    public static function init():void {
        Host::updateOrCreate([
            'ip' => HostHelpers::getHostIp()
        ]);
    }
}