<?php

namespace App\ClusterCache;

use App\ClusterCache\Drivers\MemoryDriverInterface;
use App\ClusterCache\Drivers\Shmop\ShmopDriver;

class MemoryDriver
{
    public static array $allDrivers = [
        'SHMOP' => ShmopDriver::class,
    ];

    public MemoryDriverInterface $driver;

    public function __construct(string $driverName)
    {
        if (!in_array($driverName, array_keys(self::$allDrivers))) {
            throw new \InvalidArgumentException('The memory driver "' . $driverName . '" is unavailable');
        }
        $this->driver = new (self::$allDrivers[$driverName]);
    }

    public static function fromString(string $driverName):self
    {
        return new self($driverName);
    }
}