<?php

namespace Tests\Unit\LockingMechanisms;

use Sopamo\ClusterCache\Drivers\Shmop\ShmopDriver;
use Sopamo\ClusterCache\EventLockInformation;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\LockingMechanisms\EventLocker;
use Tests\TestCase;

class EventLockerTest extends TestCase
{
    protected EventLocker $eventLocker;
    protected string $cacheKey = 'key';

    public function setUp(): void
    {
        parent::setUp();
        $shmopDriver = app(ShmopDriver::class);
        EventLockInformation::setMemoryDriver($shmopDriver);
        $this->eventLocker = app(EventLocker::class);

        $this->eventLocker->release($this->cacheKey);
    }

    /** @test */
    public function it_acquires_lock() {
        $this->assertFalse($this->eventLocker->isLocked($this->cacheKey));

        $this->eventLocker->acquire($this->cacheKey, Event::$allEvents['CACHE_KEY_IS_UPDATING']);
        $this->assertTrue($this->eventLocker->isLocked($this->cacheKey));
    }

    /** @test */
    public function it_releases_lock() {
        $this->assertFalse($this->eventLocker->isLocked($this->cacheKey));

        $this->eventLocker->acquire($this->cacheKey, Event::$allEvents['CACHE_KEY_IS_UPDATING']);
        $this->assertTrue($this->eventLocker->isLocked($this->cacheKey));

        $this->eventLocker->release($this->cacheKey);
        $this->assertFalse($this->eventLocker->isLocked($this->cacheKey));
    }
}