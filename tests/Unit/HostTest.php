<?php

namespace Sopamo\ClusterCache\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\Models\Host;
use Sopamo\ClusterCache\Tests\TestCase;

class HostTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function a_host_has_a_ip()
    {
        $host = Host::factory()->create(['ip' => '154.175.153.121']);
        $this->assertEquals('154.175.153.121', $host->ip);
    }
}