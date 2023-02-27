<?php

namespace App\Console\Commands;

use App\Service\DBLockerWithLongLocking;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\CacheEntry;
use Sopamo\ClusterCache\Models\Host;

class TestBackground extends Command
{

    protected $signature = 'clustercache:testbackground {cacheKey=key} {value=value}';

    protected $description = 'Run the background for cluster cache tests';

    protected CacheManager $cacheManager;

    public function handle(): int
    {
        app()->bind(
            DBLocker::class,
            DBLockerWithLongLocking::class
        );
        $cacheKey = $this->argument('cacheKey');
        $value = $this->argument('value');

        logger($cacheKey);

        logger('before connection ' . microtime(true));
        Config::set('database.default', 'testing');
        DB::setDefaultConnection('testing');

        logger('before cacheManager ' . microtime(true));
        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => MemoryDriver::fromString('SHMOP')]);

        logger($value);

        logger('before put ' . microtime(true));
        $this->cacheManager->put($cacheKey, $value);
        logger('after put ' . microtime(true));
        return 0;
    }

}