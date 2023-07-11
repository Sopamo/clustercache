<?php

namespace Sopamo\ClusterCache\HostCommunication;

use InvalidArgumentException;

class Event
{
    public static array $allEvents = [
        'CACHE_KEY_HAS_UPDATED' => 1,
        'TEST_CONNECTION' => 2,
        'FETCH_HOSTS' => 3,
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