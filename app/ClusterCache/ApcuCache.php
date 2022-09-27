<?php

namespace App\ClusterCache;

use App\ClusterCache\interfaces\Deletable;
use App\ClusterCache\interfaces\Readable;
use App\ClusterCache\interfaces\Writable;

class ApcuCache implements Writable, Readable, Deletable
{
    public function delete(string $key): void
    {
        apcu_delete($key);
    }

    public function exists(string $key): bool
    {
        return apcu_exists($key);
    }

    public function read(string $key)
    {
        return apcu_fetch($key);
    }

    public function write(string $key, $value): void
    {
        apcu_store($key, $value);
    }

}