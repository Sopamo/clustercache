<?php

namespace Sopamo\ClusterCache\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\Drivers\Shmop\ShmopDriver;
use Sopamo\ClusterCache\Tests\TestCase;

class CacheManagerTest extends TestCase
{
    use RefreshDatabase;
    protected CacheManager $cacheManager;
    protected array $defaultMetaInformationData = [];
    public function setUp(): void
    {
        parent::setUp();

        $shmopDriver = app(ShmopDriver::class);

        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => $shmopDriver]);
    }
}