<?php

namespace App\Services;

use Sopamo\ClusterCache\LockingMechanisms\DBLocker;

class DBLockerWithLongLocking extends DBLocker
{
    public function acquire(string $key): void
    {
        parent::acquire($key);
        sleep(2);
    }
}