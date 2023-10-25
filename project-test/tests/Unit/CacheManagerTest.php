<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\CacheManager;
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

        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => MemoryDriver::fromString('SHMOP')]);
        $this->value = Host::factory(500)->create();

        $this->cacheManager->delete('clustercache_hosts');
    }

    /** @test */
    public function put_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertTrue($this->cacheManager->put($this->cacheKey, $this->value));

    }

    /** @test */
    public function get_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));
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

    /** @test */
    public function delete_data_from_local_cache() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));

        $this->cacheManager->deleteFromLocalCache($this->cacheKey);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));
    }
}