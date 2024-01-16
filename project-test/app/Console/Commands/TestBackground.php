<?php

namespace App\Console\Commands;

use App\Services\DBLockerWithLongLocking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\HostInNetwork;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\Host;

class TestBackground extends Command
{
    protected $signature = 'clustercache:testbackground {cacheKey=key} {value=value}';

    protected $description = 'Run the background for cluster cache tests';

    public function handle(): int
    {
        app()->bind(
            DBLocker::class,
            DBLockerWithLongLocking::class
        );

        $cacheKey = $this->argument('cacheKey');
        $value = $this->argument('value');

        DB::setDefaultConnection('testing');

        Host::updateOrCreate([
            'ip' => HostInNetwork::getHostIp()
        ]);

        $cacheManager = app(CacheManager::class, ['memoryDriver' => MemoryDriver::fromString('SHMOP')]);

        $cacheManager->put($cacheKey, $value);

        return 0;
    }

}