<?php

namespace Sopamo\ClusterCache\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\Drivers\Shmop\ShmopDriver;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Tests\TestCase;

class CacheManagerTest extends TestCase
{
    use RefreshDatabase;
    protected CacheManager $cacheManager;
    protected array $defaultMetaInformationData = [];
    public function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => MemoryDriver::fromString('SHMOP')]);
    }

    /** @test */
    public function test() {
        var_dump(exec('php artisan clustercache:testbackground'));
    }
}