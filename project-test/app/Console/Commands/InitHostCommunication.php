<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sopamo\ClusterCache\HostStatus;

class InitHostCommunication extends Command
{
    protected $signature = 'clustercache:initcommunication';

    protected $description = 'Init the host';

    public function handle(): int
    {
        HostStatus::init();

        return Command::SUCCESS;
    }

}