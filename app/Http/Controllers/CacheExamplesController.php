<?php

namespace App\Http\Controllers;

use App\ClusterCache\CacheManager;
use App\ClusterCache\MemoryDriver;

class CacheExamplesController extends Controller
{
    public function index() {
        CacheManager::init(MemoryDriver::$allDrivers['SHMOP']);

        CacheManager::put('my_cache', 'value');
        logger(CacheManager::get('my_cache'));
        CacheManager::delete('my_cache');
    }
}