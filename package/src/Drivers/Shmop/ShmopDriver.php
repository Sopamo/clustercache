<?php

namespace Sopamo\ClusterCache\Drivers\Shmop;

use Exception;
use Shmop;
use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;
use Sopamo\ClusterCache\Exceptions\MemoryBlockDoesntExistException;

class ShmopDriver implements MemoryDriverInterface
{
    const METADATA_LENGTH_IN_BYTES = 8;

    public function put(string $memoryKey, mixed $value, int $length): bool
    {
        try {
            $shmop = $this->openOrCreateMemoryBlock($memoryKey, $length + self::METADATA_LENGTH_IN_BYTES,
                ShmopConnectionMode::Create);
            $dataLength = strlen($value);
            $dataToSave = pack('J', $dataLength).$value;
            shmop_write($shmop, $dataToSave, 0);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @throws MemoryBlockDoesntExistException
     */
    private function openOrCreateMemoryBlock(int $memoryKey, int $length, ShmopConnectionMode $mode): Shmop
    {
        $shmop = shmop_open($memoryKey, $mode->value, 0644, $length + self::METADATA_LENGTH_IN_BYTES);
        if (!$shmop) {
            throw new MemoryBlockDoesntExistException('the memory block "'.$memoryKey.'" doesn\'t exist');
        }
        return $shmop;
    }

    public function get(string $memoryKey, int $length): mixed
    {
        try {
            $shmop = $this->openOrCreateMemoryBlock($memoryKey, $length + self::METADATA_LENGTH_IN_BYTES,
                ShmopConnectionMode::ReadOnly);
            $dataLength = unpack('J', shmop_read($shmop, 0, self::METADATA_LENGTH_IN_BYTES))[1];
            return shmop_read($shmop, self::METADATA_LENGTH_IN_BYTES, $dataLength);
        } catch (Exception $e) {
            return null;
        }
    }

    public function delete(string $memoryKey, int $length): bool
    {
        try {
            $shmop = $this->openOrCreateMemoryBlock($memoryKey, $length + self::METADATA_LENGTH_IN_BYTES,
                ShmopConnectionMode::ReadAndWite);
            return shmop_delete($shmop);
        } catch (Exception $e) {
            return true;
        }
    }

    public function generateMemoryKey(): int
    {
        return intval(uniqid('', true), 16);
    }
}