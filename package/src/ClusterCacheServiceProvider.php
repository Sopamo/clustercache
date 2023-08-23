<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class ClusterCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'clustercache');

        $this->app->booting(function () {
            Cache::extend('clustercache', function () {
                return Cache::repository(new ClusterCacheStore(MemoryDriver::fromString(config('clustercache.driver')),
                    config('clustercache.prefix')));
            });
        });
    }

    public function boot(): void
    {
        if (!class_exists('CreateHostsTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/cache_entries_table.php.stub' => database_path('migrations/'.date('Y_m_d_His',
                        time()).'_cache_entries_table.php'),
                __DIR__.'/../database/migrations/create_hosts_table.php.stub' => database_path('migrations/'.date('Y_m_d_His',
                        time()).'_create_hosts_table.php'),
                // you can add any number of migrations here
            ], 'migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('clustercache.php'),
            ], 'config');

        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}