<?php

namespace Tests\Unit;

use Sopamo\ClusterCache\HostHelpers;
use Tests\TestCase;

class HostHelpersTest extends TestCase
{
    /**
     * @test
     */
    public function get_host_ip():void {
        $this->assertGreaterThan(0, strlen(HostHelpers::getHostIp()));
    }
}