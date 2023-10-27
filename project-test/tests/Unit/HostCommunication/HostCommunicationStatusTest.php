<?php

namespace Tests\Unit\HostCommunication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\CachedHosts;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\HostStatus;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class HostCommunicationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Host::query()->delete();

        CachedHosts::refresh();
    }
    /**
     * @test
     */
    public function init_works():void{
        $this->assertCount(0, Host::all());
        $this->assertCount(0, CachedHosts::get());

        HostStatus::init();

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostHelpers::getHostIp(), Host::first()->ip);
        $this->assertEquals([HostHelpers::getHostIp()], CachedHosts::get());

        HostStatus::init();

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostHelpers::getHostIp(), Host::first()->ip);
        $this->assertEquals([HostHelpers::getHostIp()], CachedHosts::get());
    }

    /**
     * @test
     */
    public function leave_works():void{
        $this->assertCount(0, Host::all());
        $this->assertCount(0, CachedHosts::get());

        HostStatus::init();

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostHelpers::getHostIp(), Host::first()->ip);
        $this->assertEquals([HostHelpers::getHostIp()], CachedHosts::get());

        HostStatus::leave();

        $this->assertCount(0, Host::all());
        $this->assertCount(0, CachedHosts::get());
    }
}