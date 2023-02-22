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

    protected $signature = 'clustercache:testbackground';

    protected $description = 'Run the background for cluster cache tests';

    protected CacheManager $cacheManager;

    protected string $cacheKey = 'key';

    protected array $value = [];

    public function handle(): int
    {
        DB::setDefaultConnection('testing');
        Config::set('database.default', 'testing');

        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => MemoryDriver::fromString('SHMOP')]);

        $str = Str::random(90);
        for($i = 0; $i <= 600000; $i++) {
            $this->value[] = $str;
        }

        $this->cacheManager->put($this->cacheKey, $this->value);
        return 0;
    }

}