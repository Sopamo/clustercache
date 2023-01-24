<?php

namespace App\ClusterCache\Drivers\Shmop;

enum ShmopConnectionMode: string
{
    case ReadOnly = 'a';
    case ReadAndWite = 'w';
}