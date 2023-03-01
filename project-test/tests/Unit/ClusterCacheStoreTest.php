<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ClusterCacheStoreTest extends TestCase
{
    use RefreshDatabase;

    protected string $store = 'clustercache';

    /** @test */
    public function it_sets_prefix() {
        $prefix = 'myPrefix';

        Cache::store($this->store)->setPrefix($prefix);

        $this->assertEquals($prefix . ':', Cache::store($this->store)->getPrefix());
    }

    /** @test */
    public function it_puts_data() {
        $this->assertTrue(Cache::store($this->store)->put('test', 'value'));
    }

    /** @test */
    public function it_puts_many_data() {
        $key1 = 'key1';
        $value1 = 'value1';
        $key2 = 'key2';
        $value2 = 'value2';

        $this->assertTrue(Cache::store($this->store)->putMany([
            $key1 => $value1,
            $key2 => $value2,
        ]));

        $this->assertEquals($value1, Cache::store($this->store)->get($key1));
        $this->assertEquals($value2, Cache::store($this->store)->get($key2));
    }

    /** @test */
    public function it_gets_data() {
        $key = 'key';
        $value = 'value';

        Cache::store($this->store)->put($key, $value);
        $this->assertEquals($value, Cache::store($this->store)->get($key));
    }

    /** @test */
    public function it_gets_expired_data() {
        $key = 'key';
        $value = 'value';

        Cache::store($this->store)->put($key, $value, 1);
        $this->assertEquals($value, Cache::store($this->store)->get($key));
        sleep(2);
        $this->assertNull(Cache::store($this->store)->get($key));
    }

    /** @test */
    public function it_gets_forever_data() {
        $key = 'key';
        $value = 'value';

        Cache::store($this->store)->put($key, $value);
        $this->assertEquals($value, Cache::store($this->store)->get($key));
        sleep(2);
        $this->assertEquals($value, Cache::store($this->store)->get($key));
    }

    /** @test */
    public function it_forgets_data() {
        $key = 'key';
        $value = 'value';

        Cache::store($this->store)->put($key, $value);
        $this->assertEquals($value, Cache::store($this->store)->get($key));

        $this->assertTrue(Cache::store($this->store)->forget($key));
        $this->assertNull(Cache::store($this->store)->get($key));
    }

    /** @test */
    public function it_flushes_data() {
        $key1 = 'key1';
        $value1 = 'value1';
        $key2 = 'key2';
        $value2 = 'value2';

        Cache::store($this->store)->put($key1, $value1);
        $this->assertEquals($value1, Cache::store($this->store)->get($key1));
        Cache::store($this->store)->put($key2, $value2);
        $this->assertEquals($value2, Cache::store($this->store)->get($key2));

        $this->assertTrue(Cache::store($this->store)->flush());
        $this->assertNull(Cache::store($this->store)->get($key1));
        $this->assertNull(Cache::store($this->store)->get($key2));
    }

    /** @test */
    public function it_increments_data() {
        $key = 'key';
        $value = 1;

        Cache::store($this->store)->put($key, $value);
        $this->assertEquals($value, Cache::store($this->store)->get($key));

        $this->assertEquals($value+1, Cache::store($this->store)->increment($key));
        $this->assertEquals($value+1, Cache::store($this->store)->get($key));
    }

    /** @test */
    public function it_doesnt_increment_data() {
        $key = 'key';
        $value = 'string';

        Cache::store($this->store)->put($key, $value);
        $this->assertEquals($value, Cache::store($this->store)->get($key));

        $this->assertFalse(Cache::store($this->store)->increment($key));
        $this->assertEquals($value, Cache::store($this->store)->get($key));
    }

    /** @test */
    public function it_decrements_data() {
        $key = 'key';
        $value = 1;

        Cache::store($this->store)->put($key, $value);
        $this->assertEquals($value, Cache::store($this->store)->get($key));

        $this->assertEquals($value-1, Cache::store($this->store)->decrement($key));
        $this->assertEquals($value-1, Cache::store($this->store)->get($key));
    }

    /** @test */
    public function it_doesnt_decrement_data() {
        $key = 'key';
        $value = 'string';

        Cache::store($this->store)->put($key, $value);
        $this->assertEquals($value, Cache::store($this->store)->get($key));

        $this->assertFalse(Cache::store($this->store)->decrement($key));
        $this->assertEquals($value, Cache::store($this->store)->get($key));
    }
}