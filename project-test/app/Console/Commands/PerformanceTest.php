<?php

namespace App\Console\Commands;

use App\Services\DBLockerWithLongLocking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\MemoryDriver;

class PerformanceTest extends Command
{
    protected $signature = 'clustercache:performance';

    protected $description = 'Run the performance test';

    public function handle(): int
    {
        $cacheKey = 'cache key';
        $value = $this->getRandomValue();
        $results = [];

        Cache::store('clustercache')->put($cacheKey, $value);
        Cache::store('file')->put($cacheKey, $value);

        $results['clustercache'] = [
            'driver' => 'clustercache',
            'executionTime' => $this->measureExecutionTime(fn() => Cache::store('clustercache')->get($cacheKey)),
            'difference' => '-'
        ];

        $results['file'] = [
            'driver' => 'file',
            'executionTime' => $this->measureExecutionTime(fn() => Cache::store('file')->get($cacheKey))
        ];
        $results['file']['difference'] = $results['file']['executionTime'] - $results['clustercache']['executionTime'];

        $this->table(
            ['Drive', 'Execution time', 'Difference'],
            $results
        );
        return 0;
    }

    private function getRandomValue(): array
    {
        $str = Str::random(80);
        $value = [];

        for($i = 0; $i < 9000; $i++) {
            $value[] = $str;
        }

        return $value;
    }

    private function measureExecutionTime($function) {
        $start = microtime(true);
        $function();
        return microtime(true) - $start;
    }

}