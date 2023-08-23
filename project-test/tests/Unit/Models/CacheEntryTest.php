<?php

namespace Tests\Unit\Models;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\Models\CacheEntry;
use Tests\TestCase;

class CacheEntryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function it_has_key()
    {
        $cacheEntry = CacheEntry::factory()->create(['key' => 'cache key']);
        $this->assertEquals('cache key', $cacheEntry->key);
    }

    /** @test */
    function it_has_value()
    {
        $cacheEntry = CacheEntry::factory()->create(['value' => 'cache value']);
        $this->assertEquals('cache value', $cacheEntry->value);
    }

    /** @test */
    function it_has_empty_sting_as_value()
    {
        $cacheEntry = CacheEntry::factory()->create(['value' => '']);
        $this->assertEquals('', $cacheEntry->value);
    }

    /** @test */
    function it_has_null_value()
    {
        $cacheEntry = CacheEntry::factory()->create(['value' => null]);
        $this->assertNull($cacheEntry->value);
    }

    /** @test */
    function it_has_numeric_value()
    {
        $cacheEntry = CacheEntry::factory()->create(['value' => -5]);
        $this->assertIsNumeric($cacheEntry->value);

        $cacheEntry = CacheEntry::factory()->create(['value' => 0]);
        $this->assertIsNumeric($cacheEntry->value);

        $cacheEntry = CacheEntry::factory()->create(['value' => 0.5]);
        $this->assertIsNumeric($cacheEntry->value);

        $cacheEntry = CacheEntry::factory()->create(['value' => 5]);
        $this->assertIsNumeric($cacheEntry->value);
    }

    /** @test */
    function it_has_boolean_value()
    {
        $cacheEntry = CacheEntry::factory()->create(['value' => true]);
        $this->assertTrue($cacheEntry->value);

        $cacheEntry = CacheEntry::factory()->create(['value' => false]);
        $this->assertFalse($cacheEntry->value);
    }

    /** @test */
    function it_has_array_value()
    {
        $cacheEntry = CacheEntry::factory()->create(['value' => []]);
        $this->assertEquals([], $cacheEntry->value);

        $cacheEntry = CacheEntry::factory()->create(['value' => ['foo' => 'bar']]);
        $this->assertEquals(['foo' => 'bar'], $cacheEntry->value);
    }

    /** @test */
    function it_has_created_at()
    {
        $cacheEntry = CacheEntry::factory()->create();
        $this->assertNotNull($cacheEntry->created_at);
    }

    /** @test */
    function it_has_updated_at()
    {
        $cacheEntry = CacheEntry::factory()->create();
        $this->assertNotNull($cacheEntry->updated_at);
    }

    /** @test */
    function cache_entries_cant_have_duplicated_keys()
    {
        $this->assertCount(0, CacheEntry::all());

        CacheEntry::factory()->create(['key' => 'cache key']);

        $this->assertCount(1, CacheEntry::all());
        $this->expectException(QueryException::class);

        CacheEntry::factory()->create(['key' => 'cache key']);
    }
}