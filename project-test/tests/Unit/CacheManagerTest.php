<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class CacheManagerTest extends TestCase
{
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
        Artisan::call('migrate:refresh');

        $this->cacheManager->delete($this->cacheKey);

        $this->assertTrue($this->cacheManager->put($this->cacheKey, $this->value));

    }

    /** @test */
    public function get_data() {
        Artisan::call('migrate:refresh');

        $this->cacheManager->delete($this->cacheKey);

        $this->assertNull($this->cacheManager->get($this->cacheKey));

        $this->cacheManager->put($this->cacheKey, $this->value);

        $this->assertCount($this->value->count(), $this->cacheManager->get($this->cacheKey));
    }

    /** @test */
    public function delete_data() {
        Artisan::call('migrate:refresh');

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

        $this->cacheManager->delete($key);

        exec('php artisan clustercache:testbackground \'' . $key . '\' > /dev/null 2>&1 &');
        sleep(1);

        $this->assertFalse($this->cacheManager->put($key, $this->value));
    }

    /** @test */
    public function block_deleting_data_while_putting_in_other_process() {
        Artisan::call('migrate:refresh');

        $key = 'key: block_deleting_data_while_putting_in_other_process hhh';

        $this->cacheManager->delete($key);

        exec('php artisan clustercache:testbackground \'' . $key . '\' > /dev/null 2>&1 &');
        sleep(1);

        $this->assertFalse($this->cacheManager->delete($key, $this->value));
    }

    /** @test */
    public function get_data_which_was_saved_by_other_process() {
        Artisan::call('migrate:refresh');

        $key = 'key: get_data_which_was_saved_by_other_process';
        $value = 'value';

        $this->cacheManager->delete($key);

        $this->assertNull($this->cacheManager->get($key));

        exec('php artisan clustercache:testbackground \'' . $key . '\' \'' . $value . '\'');

        $this->assertEquals($value, $this->cacheManager->get($key));
    }
}