<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\ServiceProvider;

class ClusterCacheServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {

        if (! class_exists('CreateHostsTable')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/cache_entries_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_cache_entries_table.php'),
                __DIR__ . '/../database/migrations/create_hosts_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_hosts_table.php'),
                // you can add any number of migrations here
            ], 'migrations');
        }
    }
}