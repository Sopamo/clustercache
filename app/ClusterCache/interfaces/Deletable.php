<?php

namespace App\ClusterCache\interfaces;

interface Deletable
{
    public function delete(string $key):void;
}