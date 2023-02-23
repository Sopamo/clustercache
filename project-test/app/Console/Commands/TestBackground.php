<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\Host;

class TestBackground extends Command
{

    protected $signature = 'clustercache:testbackground {cacheKey=key} {value?}';

    protected $description = 'Run the background for cluster cache tests';

    protected CacheManager $cacheManager;

    public function handle(): int
    {
        $cacheKey = $this->argument('cacheKey');
        $value = $this->argument('value');

        logger($cacheKey);

        Config::set('database.default', 'testing');
        DB::setDefaultConnection('testing');

        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => MemoryDriver::fromString('SHMOP')]);

        if(!$value) {
            $value = [];
            $str = Str::random(90);
            for($i = 0; $i <= 600000; $i++) {
                $value[] = $str;
            }
        }
        logger($value);

        $this->cacheManager->put($cacheKey, $value);
        return 0;
    }

}