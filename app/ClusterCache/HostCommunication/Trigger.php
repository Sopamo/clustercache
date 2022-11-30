<?php

namespace App\ClusterCache\HostCommunication;

use App\ClusterCache\Drivers\MemoryDriverInterface;
use App\ClusterCache\Drivers\Shmop\ShmopDriver;

class Trigger
{
    public static array $allTriggers = [
        'CACHE_KEY_IS_UPDATING' => 'cache key is updating',
        'CACHE_KEY_HAS_UPDATED' => 'cache key has updated',
        'TEST_CONNECTION' => 'test connection',
        'fetch_hosts' => 'fetch hosts',
    ];

    public string $value;

    public function __construct(string $trigger)
    {
        if (!in_array($trigger, self::$allTriggers)) {
            throw new \InvalidArgumentException('The trigger "' . $trigger . '" is unavailable');
        }
        $this->value = $trigger;
    }

    public static function fromString(string $trigger):self
    {
        return new self($trigger);
    }
}