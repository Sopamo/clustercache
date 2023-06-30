<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sopamo\ClusterCache\HostCommunication\HostCommunicationStatus;

class InitHostCommunication extends Command
{
    protected $signature = 'clustercache:initcommunication';

    protected $description = 'Init the host';

    public function handle(): int
    {
        HostCommunicationStatus::init();

        return Command::SUCCESS;
    }

}