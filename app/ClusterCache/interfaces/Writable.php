<?php

namespace App\ClusterCache\interfaces;

interface Writable
{
    public function write(string $key, $value):void;
}