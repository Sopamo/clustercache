<?php

namespace Sopamo\ClusterCache;

use InvalidArgumentException;
use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;
use Sopamo\ClusterCache\Drivers\Shmop\ShmopDriver;

class MemoryDriver
{
    public static array $allDrivers = [
        'SHMOP' => ShmopDriver::class,
    ];

    public MemoryDriverInterface $driver;

    public function __construct(string $driverName)
    {
        if (!in_array($driverName, array_keys(self::$allDrivers))) {
            throw new InvalidArgumentException('The memory driver "'.$driverName.'" is unavailable');
        }
        /** @var MemoryDriverInterface $memoryDriver */
        $memoryDriver = new (self::$allDrivers[$driverName]);
        $this->driver = $memoryDriver;
    }

    public static function fromString(string $driverName): self
    {
        return new self($driverName);
    }
}