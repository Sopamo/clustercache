<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\CachedHosts;
use Sopamo\ClusterCache\HostInNetwork;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class HostInNetworkTest extends TestCase
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
    public function get_host_ip():void {
        $this->assertGreaterThan(0, strlen(HostInNetwork::getHostIp()));
    }
    /**
     * @test
     */
    public function init_works():void{
        $this->assertCount(0, Host::all());
        $this->assertCount(0, CachedHosts::get());

        HostInNetwork::join();

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostInNetwork::getHostIp(), Host::first()->ip);
        $this->assertEquals([HostInNetwork::getHostIp()], CachedHosts::get());

        HostInNetwork::join();

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostInNetwork::getHostIp(), Host::first()->ip);
        $this->assertEquals([HostInNetwork::getHostIp()], CachedHosts::get());
    }

    /**
     * @test
     */
    public function leave_works():void{
        $this->assertCount(0, Host::all());
        $this->assertCount(0, CachedHosts::get());

        HostInNetwork::join();

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostInNetwork::getHostIp(), Host::first()->ip);
        $this->assertEquals([HostInNetwork::getHostIp()], CachedHosts::get());

        HostInNetwork::leave();

        $this->assertCount(0, Host::all());
        $this->assertCount(0, CachedHosts::get());
    }

    /**
     * @test
     */
    public function host_is_connected():void{
        $this->assertTrue(HostInNetwork::isConnected());

        HostInNetwork::markAsConnected();

        $this->assertTrue(HostInNetwork::isConnected());
    }

    /**
     * @test
     */
    public function host_is_disconnected():void{
        HostInNetwork::markAsDisconnected();

        $this->assertFalse(HostInNetwork::isConnected());
    }
}