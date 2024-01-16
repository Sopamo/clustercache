<?php

namespace Tests\Unit;

use Sopamo\ClusterCache\CachedHosts;
use Sopamo\ClusterCache\HostCommunication\Triggers\Trigger;
use Sopamo\ClusterCache\HostInNetwork;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class SingleHostTestCase extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        Trigger::setRequestHeaders([
            'Test-Mode: true',
        ]);

        Host::updateOrCreate([
            'ip' => HostInNetwork::getHostIp()
        ]);

        HostInNetwork::markAsConnected();

        CachedHosts::refresh();
    }

}