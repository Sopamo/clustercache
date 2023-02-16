<?php

namespace Sopamo\ClusterCache\Tests\Unit;

use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Tests\TestCase;

class DBLockerTest extends TestCase
{
    protected DBLocker $dbLocker;
    public function setUp(): void
    {
        parent::setUp();

        $this->dbLocker = new DBLocker();
    }

    /** @test  */
    public function it_acquires_lock_for_not_existed_cache_key() {
        $key = 'key';

        $this->assertCount(0, CacheEntry::where('key', $key)->get());

        $this->dbLocker->acquire($key);

        $cacheEntry = CacheEntry::where('key', $key)->first();

        $this->assertNotNull($cacheEntry->locked_at);

    }

    /** @test  */
    public function it_acquires_lock_for_existed_cache_key() {
        $key = 'key';

        $this->assertCount(0, CacheEntry::where('key', $key)->get());

        $cacheEntry = CacheEntry::factory()->create(['key' => $key, 'locked_at' => null]);

        $this->assertCount(1, CacheEntry::where('key', $key)->get());

        $this->dbLocker->acquire($key);

        $acquiredCacheEntry = CacheEntry::where('key', $key)->first();

        $this->assertNotNull($acquiredCacheEntry->locked_at);
        $this->assertEquals($cacheEntry->id, $acquiredCacheEntry->id);

    }

    /** @test  */
    public function it_isnt_locked_for_not_existed_cache_key() {
        $this->assertFalse($this->dbLocker->isLocked('cache_key'));
    }
}