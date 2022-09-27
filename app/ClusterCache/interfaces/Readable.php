<?php

namespace App\ClusterCache\interfaces;

interface Readable
{
    public function read(string $key);
    public function exists(string $key):bool;
}