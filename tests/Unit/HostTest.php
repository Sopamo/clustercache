<?php

namespace Sopamo\ClusterCache\Tests\Unit;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\Models\Host;
use Sopamo\ClusterCache\Tests\TestCase;

class HostTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_has_ip()
    {
        $host = Host::factory()->create(['ip' => '154.175.153.121']);
        $this->assertEquals('154.175.153.121', $host->ip);
    }

    /** @test */
    function it_has_created_at()
    {
        $host = Host::factory()->create();
        $this->assertNotNull($host->created_at);
    }

    /** @test */
    function it_has_updated_at()
    {
        $host = Host::factory()->create();
        $this->assertNotNull($host->updated_at);
    }

    /** @test */
    function hosts_cant_have_duplicated_ips()
    {
        $this->assertCount(0, Host::all());

        Host::factory()->create(['ip' => '154.175.153.121']);

        $this->assertCount(1, Host::all());
        $this->expectException(QueryException::class);

        Host::factory()->create(['ip' => '154.175.153.121']);
    }
}