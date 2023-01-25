<?php

namespace App\ClusterCache\Drivers\Shmop;

use App\ClusterCache\Drivers\MemoryDriverInterface;
use App\ClusterCache\Exceptions\MemoryBlockDoesntExistException;

class ShmopDriver implements MemoryDriverInterface
{
    const METADATA_LENGTH_IN_BYTES = 8;

    public static function put(string $memoryKey, mixed $value, int $length): bool
    {
        try {
            $shmop = self::openOrCreateMemoryBlock($memoryKey, $length, ShmopConnectionMode::Create);
            $dataLength = strlen($value);
            logger('dataLength: ' . $dataLength);
            //$dataLengthInBinary = self::decimalToBinary($dataLength, self::METADATA_LENGTH_IN_BYTES * 8);
            //logger('$dataLengthInBinary: ' . $dataLengthInBinary);
            $dataToSave = pack('J', $dataLength) . $value;
            logger('$dataToSave: ' . $dataToSave);
            shmop_write($shmop, $dataToSave, 0);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public static function get(string $memoryKey, int $length): mixed
    {
        try{
            $shmop = self::openOrCreateMemoryBlock($memoryKey,  $length, ShmopConnectionMode::ReadOnly);
            $dataLength = unpack('J', shmop_read($shmop, 0, self::METADATA_LENGTH_IN_BYTES))[1];
            logger('$dataLength in GET: ' . $dataLength);
            return shmop_read($shmop, self::METADATA_LENGTH_IN_BYTES, $dataLength);
        } catch (MemoryBlockDoesntExistException $e) {
            return null;
        }
    }

    public static function delete(string $memoryKey): bool
    {
        // TODO: Implement delete() method.
    }

    /**
     * @throws MemoryBlockDoesntExistException
     */
    private static function openOrCreateMemoryBlock(int $memoryKey, int $length, ShmopConnectionMode $mode): \Shmop
    {
        $shmop = shmop_open($memoryKey, $mode->value, 0644, $length);
        if(!$shmop) {
            throw new MemoryBlockDoesntExistException('the memory block "' . $memoryKey . '" doesn\'t exist');
        }
        return $shmop;
    }

    public static function generateMemoryKey():int {
        return intval(uniqid('', true), 16);
    }

/*    private static function decimalToBinary(int $number, int $bits = 32): string {
        return sprintf("%0" . $bits . "b", $number);
    }*/
}