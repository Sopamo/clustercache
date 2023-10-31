<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Sopamo\ClusterCache\CacheKey;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\Exceptions\HostIsMarkedAsDisconnectedException;
use Sopamo\ClusterCache\HostInNetwork;
use Sopamo\ClusterCache\LocalCacheManager;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class CacheManagerTest extends SingleHostTestCase
{
    use RefreshDatabase;

    protected CacheManager $cacheManager;
    protected string $cacheKey = 'key';
    protected Collection|Model $value;
    public function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = app(CacheManager::class);
        $this->value = Host::factory(500)->create();
    }

    /** @test */
    public function put_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertTrue($this->cacheManager->put($this->cacheKey, $this->value));

    }

    /** @test */
    public function put_data_with_not_allowed_key() {
        $this->expectException(InvalidArgumentException::class);
        $this->cacheManager->put(CacheKey::INTERNAL_USED_KEYS['hosts'], $this->value);

    }

    /** @test */
    public function get_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        Config::set('clustercache.disconnected_mode', 'exception');
        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));

        Config::set('clustercache.disconnected_mode', 'db');
        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));
    }

    /** @test */
    public function get_expired_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value, 1);
        sleep(2);

        $this->assertNull($this->cacheManager->get($this->cacheKey));
    }

    /** @test */
    public function get_data_from_disconnected_host() {
        /** @var LocalCacheManager $localCacheManager */
        $localCacheManager = app(LocalCacheManager::class);

        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        HostInNetwork::markAsDisconnected();

        Config::set('clustercache.disconnected_mode', 'exception');

        $this->expectException(HostIsMarkedAsDisconnectedException::class);
        $this->cacheManager->get($this->cacheKey);

        Config::set('clustercache.disconnected_mode', 'db');
        $localCacheManager->put($this->cacheKey, $this->value . '2', Carbon::now()->getTimestamp());
        $this->assertEquals($this->value, $this->cacheManager->get($this->cacheKey));
    }

    /** @test */
    public function update_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));

        $this->value = Host::factory(800)->create();

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));
    }

    /** @test */
    public function delete_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));

        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));
    }
}