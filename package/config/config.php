<?php

return [
    'disconnected_mode' => env('CLUSTERCACHE_DISCONNECTED_MODE', 'db'), // or "exception"
    'driver' => 'SHMOP',
    'prefix' => 'clusterCache_',
    'protocol' => env('CLUSTERCACHE_PROTOCOL', 'https'),
];