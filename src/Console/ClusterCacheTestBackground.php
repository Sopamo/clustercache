<?php

namespace Sopamo\ClusterCache\Console;

use Illuminate\Console\Command;

class ClusterCacheTestBackground extends Command
{
    protected $signature = 'clustercache:testbackground';

    protected $description = 'Run the test background for ClusterCache';

    protected $hidden = true;

    public function handle()
    {
        echo 'a';
    }

}