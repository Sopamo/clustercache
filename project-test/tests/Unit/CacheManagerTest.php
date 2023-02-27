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
    public function delete_data() {
        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));

        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));
    }

    /** @test */
    public function block_putting_data_while_putting_in_other_process() {
        $key = 'key: block_putting_data_while_putting_in_other_process';

        logger('Test: before delete ' . microtime(true));
        logger($this->cacheManager->delete($key));
        logger('CacheEntry::where');
        logger(json_encode(CacheEntry::where('key', $key)->first()));

        logger('Test: before command ' . microtime(true));
        exec('php artisan clustercache:testbackground \'' . $key . '\' > /dev/null 2>&1 &');
        logger('Test: before sleep ' . microtime(true));
        sleep(2);
        logger('Test: after sleep ' . microtime(true));

        logger('CacheEntry::where');
        logger(json_encode(CacheEntry::where('key', $key)->first()));

        $this->assertFalse($this->cacheManager->put($key, $this->value));
    }

    /** @test */
    public function get_data_which_was_saved_by_other_process() {
        $key = 'key: gzgzgztztztz';
        $value = 'value';

        logger('Test: before deleting ' . microtime(true));
        logger($this->cacheManager->delete($key));
        logger('Test: after deleting ' . microtime(true));

        $this->assertNull($this->cacheManager->get($key));
        logger('Test: after get ' . microtime(true));

        exec('php artisan clustercache:testbackground \'' . $key . '\' \'' . $value . '\'');
        logger('Test: after artisan '. microtime(true));

        $this->assertEquals($value, $this->cacheManager->get($key));
        logger('Test: after get 2 '. microtime(true));
    }
}