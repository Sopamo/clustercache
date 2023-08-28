<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\Models\DisconnectedHost;
use Tests\TestCase;

class DisconnectedHostTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_has_from()
    {
        $host = DisconnectedHost::factory()->create(['from' => '154.175.153.121', 'to' => '154.175.153.122']);
        $this->assertEquals('154.175.153.121', $host->from);
    }

    /** @test */
    function it_has_to()
    {
        $host = DisconnectedHost::factory()->create(['from' => '154.175.153.121', 'to' => '154.175.153.122']);
        $this->assertEquals('154.175.153.122', $host->to);
    }

    /** @test */
    function it_has_created_at()
    {
        $host = DisconnectedHost::factory()->create(['from' => '154.175.153.121', 'to' => '154.175.153.122']);
        $this->assertNotNull($host->created_at);
    }

    /** @test */
    function it_has_updated_at()
    {
        $host = DisconnectedHost::factory()->create(['from' => '154.175.153.121', 'to' => '154.175.153.122']);
        $this->assertNotNull($host->updated_at);
    }
}