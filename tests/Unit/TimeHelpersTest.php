<?php

namespace Sopamo\ClusterCache\Tests\Unit;

use Sopamo\ClusterCache\Tests\TestCase;
use Sopamo\ClusterCache\TimeHelpers;

class TimeHelpersTest extends TestCase
{
    /** @test  */
    public function get_now_from_db() {
        $this->assertStringMatchesFormat('%d%d%d%d-%d%d-%d%d %d%d:%d%d:%d%d', TimeHelpers::getNowFromDB());
    }

    /** @test  */
    public function get_time_shift() {
        $this->assertIsNumeric(TimeHelpers::getTimeShift());
    }
}