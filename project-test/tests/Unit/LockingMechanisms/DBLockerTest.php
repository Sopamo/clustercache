<?php

namespace Tests\Unit\LockingMechanisms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\Models\CacheEntry;
use Tests\TestCase;

class DBLockerTest extends TestCase
{
    use RefreshDatabase;

    protected DBLocker $dbLocker;
    protected string $cacheKey = 'key';
    public function setUp(): void
    {
        parent::setUp();

        $this->dbLocker = app(DBLocker::class);
    }

    /** @test */
    public function it_acquires_lock_for_not_existed_cache_key() {
        $this->assertCount(0, CacheEntry::where('key', $this->cacheKey)->get());

        $this->dbLocker->acquire($this->cacheKey);

        $cacheEntry = CacheEntry::where('key', $this->cacheKey)->first();

        $this->assertNotNull($cacheEntry->locked_at);

    }

    /** @test */
    public function it_acquires_lock_for_existed_cache_key() {
        $this->assertCount(0, CacheEntry::where('key', $this->cacheKey)->get());

        $cacheEntry = CacheEntry::factory()->create(['key' => $this->cacheKey, 'locked_at' => null]);

        $this->assertCount(1, CacheEntry::where('key', $this->cacheKey)->get());

        $this->dbLocker->acquire($this->cacheKey);

        $acquiredCacheEntry = CacheEntry::where('key', $this->cacheKey)->first();

        $this->assertNotNull($acquiredCacheEntry->locked_at);
        $this->assertEquals($cacheEntry->id, $acquiredCacheEntry->id);

    }

    /** @test  */
    public function it_isnt_locked_for_not_existed_cache_key() {
        $this->assertFalse($this->dbLocker->isLocked('cache_key'));
    }
    /** @test  */
    public function it_is_locked() {
        $this->assertCount(0, CacheEntry::where('key', $this->cacheKey)->get());

        $this->dbLocker->acquire($this->cacheKey);

        $this->assertTrue($this->dbLocker->isLocked($this->cacheKey));
    }
    /** @test  */
    public function lock_is_expired() {
        $this->assertCount(0, CacheEntry::where('key', $this->cacheKey)->get());

        $this->dbLocker->acquire($this->cacheKey);

        $this->assertTrue($this->dbLocker->isLocked($this->cacheKey));

        DBLocker::$timeout = 1;

        sleep(2);

        $this->assertFalse($this->dbLocker->isLocked($this->cacheKey));

        DBLocker::$timeout = 30;
    }
    /** @test  */
    public function lock_is_locked_and_not_expired() {
        $this->assertCount(0, CacheEntry::where('key', $this->cacheKey)->get());

        $this->dbLocker->acquire($this->cacheKey);

        $this->assertTrue($this->dbLocker->isLocked($this->cacheKey));

        sleep(2);

        $this->assertTrue($this->dbLocker->isLocked($this->cacheKey));
    }
}