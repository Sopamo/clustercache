<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostCommunication\Triggers\Trigger;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;
use Tests\TestCase;

class SingleHostTestCase extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        Host::updateOrCreate([
            'ip' => HostHelpers::getHostIp()
        ]);

        Cache::store($this->store)->put('clustercache_hosts', Host::pluck('ip'));
    }

}