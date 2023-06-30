<?php

namespace Sopamo\ClusterCache\HostCommunication;

use InvalidArgumentException;

class Event
{
    public static array $allEvents = [
        'CACHE_KEY_IS_UPDATING' => 1,
        'CACHE_KEY_HAS_UPDATED' => 2,
        'CACHE_KEY_UPDATING_HAS_CANCELED' => 3,
        'TEST_CONNECTION' => 4,
        'FETCH_HOSTS' => 5,
    ];

    public int $value;

    public function __construct(int $event)
    {
        if (!in_array($event, self::$allEvents)) {
            throw new InvalidArgumentException('The event "'.$event.'" is unavailable');
        }
        $this->value = $event;
    }

    public static function fromInt(int $event): self
    {
        return new self($event);
    }
}