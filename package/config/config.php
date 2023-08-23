<?php

return [
    'driver' => 'SHMOP',
    'prefix' => 'clusterCache_',
    'protocol' => env('CLUSTERCACHE_PROTOCOL', 'https'),
];