<?php

namespace Sopamo\ClusterCache\Drivers\Shmop;

use Exception;
use Shmop;
use Sopamo\ClusterCache\Drivers\MemoryDriverInterface;
use Sopamo\ClusterCache\Exceptions\MemoryBlockDoesntExistException;
use Sopamo\ClusterCache\Exceptions\NotExistedFunctionException;

class ShmopDriver implements MemoryDriverInterface
{
    const METADATA_LENGTH_IN_BYTES = 8;

    /**
     * @throws NotExistedFunctionException
     */
    public function put(string $memoryKey, mixed $value, int $length): bool
    {
        if (!function_exists('shmop_write')) {
            throw new NotExistedFunctionException('shmop_write doesnt exist');
        }

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
     * @throws MemoryBlockDoesntExistException|NotExistedFunctionException
     */
    private function openOrCreateMemoryBlock(int $memoryKey, int $length, ShmopConnectionMode $mode): Shmop
    {
        if (!function_exists('shmop_open')) {
            throw new NotExistedFunctionException('shmop_open doesnt exist');
        }

        $shmop = shmop_open($memoryKey, $mode->value, 0644, $length + self::METADATA_LENGTH_IN_BYTES);
        if (!$shmop) {
            throw new MemoryBlockDoesntExistException('the memory block "'.$memoryKey.'" doesn\'t exist');
        }
        return $shmop;
    }

    /**
     * @throws NotExistedFunctionException
     */
    public function get(string $memoryKey, int $length): mixed
    {
        if (!function_exists('shmop_read')) {
            throw new NotExistedFunctionException('shmop_read doesnt exist');
        }

        try {
            $shmop = $this->openOrCreateMemoryBlock($memoryKey, $length + self::METADATA_LENGTH_IN_BYTES,
                ShmopConnectionMode::ReadOnly);
            $dataLength = unpack('J', shmop_read($shmop, 0, self::METADATA_LENGTH_IN_BYTES))[1];
            return shmop_read($shmop, self::METADATA_LENGTH_IN_BYTES, $dataLength);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @throws NotExistedFunctionException
     */
    public function delete(string $memoryKey, int $length): bool
    {
        if (!function_exists('shmop_delete')) {
            throw new NotExistedFunctionException('shmop_delete doesnt exist');
        }

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