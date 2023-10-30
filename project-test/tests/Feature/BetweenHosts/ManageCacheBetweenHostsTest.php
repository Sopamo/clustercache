<?php

namespace Tests\Feature\BetweenHosts;

use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\Exceptions\PutCacheException;
use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;

class ManageCacheBetweenHostsTest extends BetweenHostsTestCase
{

    /** @test */
    public function get_cache_from_another_hosts() {
        $this->saveInformationAboutAllConnectedHosts();

        $cacheKey = 'key';
        $cacheValue = 'value';

        Cache::store($this->store)->put($cacheKey, $cacheValue);

        $this->assertEquals($cacheValue, Cache::store($this->store)->get($cacheKey));

        $response = $this->get($this->getTestApiUrl(self::HOST_CLUSTERCACHE2) . 'get/' . $cacheKey);
        $response->assertStatus(200);
        $response->assertContent($cacheValue);

        $response = $this->get($this->getTestApiUrl(self::HOST_CLUSTERCACHE3) . 'get/' . $cacheKey);
        $response->assertStatus(200);
        $response->assertContent($cacheValue);

        $this->assertTrue(Cache::store($this->store)->delete($cacheKey));

        $response = $this->get($this->getTestApiUrl(self::HOST_CLUSTERCACHE2) . 'get/' . $cacheKey);
        $response->assertNoContent(200);

        $response = $this->get($this->getTestApiUrl(self::HOST_CLUSTERCACHE3) . 'get/' . $cacheKey);
        $response->assertNoContent(200);
    }

    /** @test */
    public function put_cache_in_another_host() {
        $this->saveInformationAboutAllConnectedHosts();

        $cacheKey = 'key';
        $cacheValue = 'value';

        Cache::store($this->store)->delete($cacheKey);

        $response = $this->post($this->getTestApiUrl(self::HOST_CLUSTERCACHE2) . 'put/' . $cacheKey, ['cacheValue' => $cacheValue]);
        $response->assertSuccessful();

        $this->assertEquals($cacheValue, Cache::store($this->store)->get($cacheKey));
    }

    /** @test */
    public function put_cache_by_host_marked_as_disconnected() {
        $this->expectException(PutCacheException::class);
        Cache::store($this->store)->put('key', 'value');
    }

    /** @test */
    public function put_cache_by_host_which_has_not_connection_to_at_least_half_of_hosts() {
        $host = new Host();
        $host->ip = self::HOST_CLUSTERCACHE1;
        $host->save();
        $host = new Host();
        $host->ip = self::HOST_CLUSTERCACHE2;
        $host->save();

        $host = new Host();
        $host->ip = self::HOST_DISCONNECTED_HOST1;
        $host->save();

        $host = new Host();
        $host->ip = self::HOST_DISCONNECTED_HOST2;
        $host->save();

        Cache::store($this->store)->put('clustercache_hosts', Host::pluck('ip'));

        $this->expectException(PutCacheException::class);
        Cache::store($this->store)->put('key', 'value');
        $this->assertNull(Host::where('ip', '!', HostHelpers::getHostIp())->first());
    }

    private function saveInformationAboutAllConnectedHosts():void {
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

    private function getTestApiUrl(string $host): string
    {
        return config('clustercache.protocol') . '://' . $host . '/clustercache/api/test/';
    }
}