<?php

namespace Tests\Feature\BetweenHosts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\HostCommunication\Triggers\Trigger;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class BetweenHostsTestCase extends TestCase
{
    use RefreshDatabase;

    const HOST_CLUSTERCACHE1 = '10.5.0.7';
    const HOST_CLUSTERCACHE2 = '10.5.0.8';
    const HOST_CLUSTERCACHE3 = '10.5.0.9';
    const HOST_DISCONNECTED_HOST = '10.5.0.10';
    public function setUp(): void
    {
        parent::setUp();

        Trigger::setRequestHeaders([
            'Test-Mode: true',
        ]);
    }

}