<?php

namespace App\Service;

use Sopamo\ClusterCache\LockingMechanisms\DBLocker;
use Sopamo\ClusterCache\Models\CacheEntry;

class DBLockerWithLongLocking extends DBLocker
{
    public function acquire(string $key): void
    {
        logger('DBLockerWithLongLocking: before acquire ' . microtime(true));
        parent::acquire($key);
        logger('DBLockerWithLongLocking: before sleep ' . microtime(true));
        logger('CacheEntry::where in DBLockerWithLongLocking');
        logger(json_encode(CacheEntry::where('key', $key)->first()));
        sleep(4);
        logger('DBLockerWithLongLocking: after sleep ' . microtime(true));
    }
}