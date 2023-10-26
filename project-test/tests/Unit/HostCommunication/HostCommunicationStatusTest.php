<?php

namespace Tests\Unit\HostCommunication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\HostCommunicationStatus;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class HostCommunicationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected string $store = 'clustercache';
    protected string $key = 'clustercache_hosts';
    /**
     * @test
     */
    public function init_works():void{
        Cache::store($this->store)->delete($this->key);

        $this->assertCount(0, Host::all());
        $this->assertNull(Cache::store($this->store)->get($this->key));

        HostCommunicationStatus::init();
        Cache::store($this->store)->put($this->key, Host::pluck('ip'));

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostHelpers::getHostIp(), Host::first()->ip);
        $this->assertEquals(collect([HostHelpers::getHostIp()]), Cache::store($this->store)->get($this->key));

        HostCommunicationStatus::init();
        Cache::store($this->store)->put($this->key, Host::pluck('ip'));

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostHelpers::getHostIp(), Host::first()->ip);
        $this->assertEquals(collect([HostHelpers::getHostIp()]), Cache::store($this->store)->get($this->key));
    }

    /**
     * @test
     */
    public function leave_works():void{
        Cache::store($this->store)->delete($this->key);

        $this->assertCount(0, Host::all());
        $this->assertNull(Cache::store($this->store)->get($this->key));

        HostCommunicationStatus::init();
        Cache::store($this->store)->put($this->key, Host::pluck('ip'));

        $this->assertCount(1, Host::all());
        $this->assertEquals(HostHelpers::getHostIp(), Host::first()->ip);
        $this->assertEquals(collect([HostHelpers::getHostIp()]), Cache::store($this->store)->get($this->key));

        HostCommunicationStatus::leave();
        Cache::store($this->store)->put($this->key, Host::pluck('ip'));

        $this->assertCount(0, Host::all());
        $this->assertEquals(collect(), Cache::store($this->store)->get($this->key));
    }
}