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
    public function put(int|string $memoryKey, mixed $value, int $length): bool
    {
        if (!function_exists('shmop_write')) {
            throw new NotExistedFunctionException('shmop_write doesnt exist');
        }

        if(is_string($memoryKey)) {
            $memoryKey = intval($memoryKey, 16);
        }

        try {
            $shmop = $this->openOrCreateMemoryBlock($memoryKey, $length + self::METADATA_LENGTH_IN_BYTES,
                ShmopConnectionMode::Create);
            $dataLength = strlen($value);
            $dataToSave = pack('J', $dataLength).$value;
            shmop_write($shmop, $dataToSave, 0);

            return true;
        } catch (Exception $e) {

//            logger("Putting Exception: " . $e->getMessage());
//            logger("Putting Exception Trace: " . $e->getTraceAsString());
//            logger("$memoryKey: " . $memoryKey);
            return false;
        }
    }

    /**
     * @throws MemoryBlockDoesntExistException|NotExistedFunctionException
     */
    private function openOrCreateMemoryBlock(string|int $memoryKey, int $length, ShmopConnectionMode $mode): Shmop
    {
        if (!function_exists('shmop_open')) {
            throw new NotExistedFunctionException('shmop_open doesnt exist');
        }

        if(is_string($memoryKey)) {
            $memoryKey = intval($memoryKey, 16);
        }

        $shmop = shmop_open($memoryKey, $mode->value, 0666, $length + self::METADATA_LENGTH_IN_BYTES);
        logger("ShmopConnectionMode: $mode->value");
        if (!$shmop) {
            throw new MemoryBlockDoesntExistException('the memory block "'.$memoryKey.'" doesn\'t exist');
        }
        return $shmop;
    }

    /**
     * @throws NotExistedFunctionException
     */
    public function get(string|int $memoryKey, int $length): mixed
    {
        if (!function_exists('shmop_read')) {
            throw new NotExistedFunctionException('shmop_read doesnt exist');
        }

        if(is_string($memoryKey)) {
            $memoryKey = intval($memoryKey, 16);
        }

        try {
            $shmop = $this->openOrCreateMemoryBlock($memoryKey, $length + self::METADATA_LENGTH_IN_BYTES,
                ShmopConnectionMode::ReadOnly);
            $unpackData = unpack('J', shmop_read($shmop, 0, self::METADATA_LENGTH_IN_BYTES));
            if(!$unpackData)  {
                throw new Exception('Unpack string contains errors');
            }
            $dataLength = $unpackData[1];
            return shmop_read($shmop, self::METADATA_LENGTH_IN_BYTES, $dataLength);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @throws NotExistedFunctionException
     */
    public function delete(string|int $memoryKey, int $length): bool
    {
        if (!function_exists('shmop_delete')) {
            throw new NotExistedFunctionException('shmop_delete doesnt exist');
        }

        if(is_string($memoryKey)) {
            $memoryKey = intval($memoryKey, 16);
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