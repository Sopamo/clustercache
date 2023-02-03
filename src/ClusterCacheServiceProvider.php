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
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}