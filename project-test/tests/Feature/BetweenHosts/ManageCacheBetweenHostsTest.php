<?php

namespace Tests\Feature\BetweenHosts;

use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;
use Sopamo\ClusterCache\Models\Host;

class ManageCacheBetweenHostsTest extends BetweenHostsTestCase
{

    protected string $store = 'clustercache';

    public function setUp(): void
    {
        parent::setUp();

        $host = new Host();
        $host->ip = self::HOST_CLUSTERCACHE1;
        $host->save();

        $host = new Host();
        $host->ip = self::HOST_CLUSTERCACHE2;
        $host->save();

        $host = new Host();
        $host->ip = self::HOST_CLUSTERCACHE3;
        $host->save();

        Cache::store($this->store)->put('clustercache_hosts', Host::pluck('ip'));
    }
    /** @test */
    public function get_cache_from_another_hosts() {
        $cacheKey = 'key';
        $cacheValue = 'value';

        logger(json_encode(Host::all()));

        Cache::store($this->store)->put($cacheKey, $cacheValue);

        $this->assertEquals($cacheValue, Cache::store($this->store)->get($cacheKey));

        $response = $this->get($this->getTestApiUrl(self::HOST_CLUSTERCACHE2) . 'get/' . $cacheKey);
        logger('content: ' .  $response->getContent());
        $response->assertStatus(200);
        $response->assertContent($cacheValue);
    }

    private function getTestApiUrl(string $host): string
    {
        return config('clustercache.protocol') . '://' . $host . '/clustercache/api/test/';
    }
}