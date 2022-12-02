<?php

namespace App\ClusterCache\HostCommunication;

class Event
{
    public static array $allEvents = [
        'CACHE_KEY_IS_UPDATING' => 'cache key is updating',
        'CACHE_KEY_HAS_UPDATED' => 'cache key has updated',
        'TEST_CONNECTION' => 'test connection',
        'FETCH_HOSTS' => 'fetch hosts',
    ];

    public string $value;

    public function __construct(string $event)
    {
        if (!in_array($event, self::$allEvents)) {
            throw new \InvalidArgumentException('The event "' . $event . '" is unavailable');
        }
        $this->value = $event;
    }

    public static function fromString(string $event):self
    {
        return new self($event);
    }
}