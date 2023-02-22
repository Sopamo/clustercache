<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class CacheManagerTest extends TestCase
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
    }

    /** @test */
    public function put_data() {
        $this->assertTrue($this->cacheManager->put($this->cacheKey, $this->value));

        $this->cacheManager->delete($this->cacheKey);
    }

    /** @test */
    public function block_putting_data_while_putting_in_other_process() {
        exec('php artisan clustercache:testbackground > /dev/null 2>&1 &');
        usleep(500000);
        $this->assertFalse($this->cacheManager->put($this->cacheKey, $this->value));

        $this->cacheManager->delete($this->cacheKey);
    }

    /** @test */
    public function get_data() {
        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));

        $this->cacheManager->delete($this->cacheKey);
    }
}