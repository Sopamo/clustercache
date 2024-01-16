<?php

namespace Tests\Unit;

use App\Services\MemoryBlockLockerWithInfiniteLocking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Sopamo\ClusterCache\Exceptions\ExpiredLocalCacheKeyException;
use Sopamo\ClusterCache\Exceptions\MemoryBlockIsLockedException;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\LocalCacheManager;
use Sopamo\ClusterCache\LockingMechanisms\MemoryBlockLocker;
use Sopamo\ClusterCache\Models\Host;

class LocalCacheManagerTest extends SingleHostTestCase
{
    use RefreshDatabase;

    protected LocalCacheManager $localCacheManager;
    protected string $cacheKey = 'key';
    protected string $value = 'value';
    public function setUp(): void
    {
        parent::setUp();

        $this->localCacheManager = app(LocalCacheManager::class);
        $this->localCacheManager->clear();
        $this->value = Host::factory(500)->create();
    }

    /** @test */
    public function put_data() {
        $this->assertTrue($this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->getTimestamp()));

    }

    /** @test */
    public function get_data() {
        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);

        $this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->getTimestamp());

        $this->assertEquals($this->value, $this->localCacheManager->get($this->cacheKey));
    }

    /** @test */
    public function get_expired_data() {
        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);

        $this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->subMinute()->getTimestamp(), 5);

        $this->expectException(ExpiredLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);
    }

    /** @test */
    public function get_locked_data() {
        app()->bind(
            MemoryBlockLocker::class,
            MemoryBlockLockerWithInfiniteLocking::class
        );

        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);

        $this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->getTimestamp());

        $this->expectException(MemoryBlockIsLockedException::class);
        $this->localCacheManager->get($this->cacheKey);
    }

    /** @test */
    public function update_data() {
        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);

        $this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->getTimestamp());

        $this->assertEquals($this->value, $this->localCacheManager->get($this->cacheKey));

        $this->value = 'value2';

        $this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->getTimestamp());

        $this->assertEquals($this->value, $this->localCacheManager->get($this->cacheKey));
    }

    /** @test */
    public function delete_data() {
        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);

        $this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->getTimestamp());

        $this->assertEquals($this->value, $this->localCacheManager->get($this->cacheKey));

        $this->localCacheManager->delete($this->cacheKey);

        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);
    }

    /** @test */
    public function clear_data() {
        $key2 = $this->cacheKey . '2';

        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);
        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($key2);

        $this->localCacheManager->put($this->cacheKey, $this->value, Carbon::now()->getTimestamp());
        $this->assertEquals($this->value, $this->localCacheManager->get($this->cacheKey));
        $this->localCacheManager->put($key2, $this->value, Carbon::now()->getTimestamp());
        $this->assertEquals($this->value, $this->localCacheManager->get($key2));

        $this->localCacheManager->clear();

        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($this->cacheKey);
        $this->expectException(NotFoundLocalCacheKeyException::class);
        $this->localCacheManager->get($key2);
    }
}