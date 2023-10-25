<?php

namespace Tests\Feature\BetweenHosts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\HostCommunication\Triggers\Trigger;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class BetweenHostsTestCase extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();

        Trigger::setRequestHeaders([
            'Test-Mode: true',
        ]);
    }

}