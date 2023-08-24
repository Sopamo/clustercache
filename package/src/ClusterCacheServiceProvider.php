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
        $publishMigrations = [];
        if (!class_exists('CreateCacheEntriesTable')) {
            $publishMigrations[__DIR__.'/../database/migrations/cache_entries_table.php.stub'] = database_path('migrations/'.date('Y_m_d_His',
                        time()).'_cache_entries_table.php');
        }
        if (!class_exists('CreateHostsTable')) {
            $publishMigrations[__DIR__.'/../database/migrations/create_hosts_table.php.stub'] = database_path('migrations/'.date('Y_m_d_His',
                    time()).'_create_hosts_table.php');
        }
        if (!class_exists('CreateDisconnectedHostsTable')) {
            $publishMigrations[__DIR__.'/../database/migrations/create_disconnected_hosts_table.php.stub'] = database_path('migrations/'.date('Y_m_d_His',
                    time()).'_create_disconnected_hosts_table.php');
        }
        $this->publishes($publishMigrations, 'migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('clustercache.php'),
            ], 'config');

        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}