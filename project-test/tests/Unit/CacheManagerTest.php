<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class CacheManagerTest extends TestCase
{
    use DatabaseMigrations;
    protected CacheManager $cacheManager;
    protected string $cacheKey = 'key';
    protected Collection|Model $value;
    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:refresh');

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
        Artisan::call('migrate:refresh');

        $key = 'key: block_putting_data_while_putting_in_other_process hhh';

        logger($this->cacheManager->delete($key));
        logger(json_encode(CacheEntry::where('key', $key)->first()));
        sleep(6);

        exec('php artisan clustercache:testbackground \'' . $key . '\' > /dev/null 2>&1 &');
        sleep(1);

        logger(json_encode(CacheEntry::where('key', $key)->first()));

        logger('Test: before put ' . microtime(true));
        logger('CacheEntry::where');
        logger(json_encode(CacheEntry::where('key', $key)->first()));
        $isPut = $this->cacheManager->put($key, $this->value);
        logger('isPut: ' . $isPut);
        sleep(11);
        logger(json_encode(CacheEntry::where('key', $key)->first()));

        $this->assertFalse($isPut);
    }

    /** @test */
    public function get_data_which_was_saved_by_other_process() {
        $key = 'key: gzgzgztztzrfaaaggatg';
        $value = 'value';

        $cacheEntry = new CacheEntry();
        $cacheEntry->key = $key;
        $cacheEntry->value = $value;

        logger('Test: before save ' . microtime(true));
        $cacheEntry->save();

        logger('Test: before exec ' . microtime(true));
        exec('php artisan clustercache:testbackground \'' . $key . '\' \'' . $value . '\'');
        logger('Test: after exec ' . microtime(true));

        logger('Test: before deleting ' . microtime(true));
        logger($this->cacheManager->delete($key));
        logger('Test: after deleting ' . microtime(true));

        $this->assertNull($this->cacheManager->get($key));
        logger('Test: after get ' . microtime(true));

        //Artisan::call('clustercache:testbackground \'' . $key . '\' \'' . $value . '\'');

        exec('php artisan clustercache:testbackground \'' . $key . '\' \'' . $value . '\'');
        logger('Test: after artisan '. microtime(true));

        $this->assertEquals($value, $this->cacheManager->get($key));
        logger('Test: after get 2 '. microtime(true));
    }
}