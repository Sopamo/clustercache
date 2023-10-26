<?php

namespace Tests\Feature\BetweenHosts;

use Sopamo\ClusterCache\HostCommunication\Triggers\TestConnectionTrigger;

class ConnectionBetweenHostsTest extends BetweenHostsTestCase
{
    /** @test */
    public function can_connect_to_all_hosts() {
        $testConnectionTrigger = app(TestConnectionTrigger::class);

        $host2 = $testConnectionTrigger->handle(self::HOST_CLUSTERCACHE2);
        $this->assertTrue($host2);

        $host3 = $testConnectionTrigger->handle(self::HOST_CLUSTERCACHE3);
        $this->assertTrue($host3);

        $host3 = $testConnectionTrigger->handle(self::HOST_CLUSTERCACHE3);
        $this->assertTrue($host3);

        $host4 = $testConnectionTrigger->handle(self::HOST_DISCONNECTED_HOST);
        $this->assertFalse($host4);
    }
}