<?php

namespace Sopamo\ClusterCache\Tests\Unit\LockingMechanisms;

use Illuminate\Support\Carbon;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Tests\TestCase;

class DBLockerTest extends TestCase
{
    protected DBLocker $dbLocker;
    public function setUp(): void
    {
        parent::setUp();

        $this->dbLocker = app(DBLocker::class);
        $this->dbLocker = $this->getMockBuilder(DBLocker::class)
            ->enableOriginalConstructor()
            ->enableOriginalClone()
            ->getMock();

        $this->dbLocker->method('getNowFromDB')->willReturn(Carbon::now()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     * @TODO FIX using NOW() from DB
     * @return void
     */
    public function it_acquires_lock_for_not_existed_cache_key() {
        $this->markTestSkipped();
        $key = 'key';

        $this->assertCount(0, CacheEntry::where('key', $key)->get());

        $this->dbLocker->acquire($key);

        $cacheEntry = CacheEntry::where('key', $key)->first();

        $this->assertNotNull($cacheEntry->locked_at);

    }

    /**
     * @test
     * @TODO FIX using NOW() from DB
     * @return void
     */
    public function it_acquires_lock_for_existed_cache_key() {
        $this->markTestSkipped();
        $key = 'key';

        $this->assertCount(0, CacheEntry::where('key', $key)->get());

        $cacheEntry = CacheEntry::factory()->create(['key' => $key, 'locked_at' => null]);

        $this->assertCount(1, CacheEntry::where('key', $key)->get());

        $this->dbLocker->acquire($key);

        $acquiredCacheEntry = CacheEntry::where('key', $key)->first();
        var_dump($acquiredCacheEntry);

        $this->assertNotNull($acquiredCacheEntry->locked_at);
        $this->assertEquals($cacheEntry->id, $acquiredCacheEntry->id);

    }

    /** @test  */
    public function it_isnt_locked_for_not_existed_cache_key() {
        $this->assertFalse($this->dbLocker->isLocked('cache_key'));
    }
    /** @test  */
    public function it_is_locked() {
        $this->markTestSkipped();
        // TODO
    }
    /** @test  */
    public function lock_is_expired() {
        $this->markTestSkipped();
        // TODO
    }
}