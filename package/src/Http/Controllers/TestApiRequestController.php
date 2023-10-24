<?php

namespace Sopamo\ClusterCache\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Sopamo\ClusterCache\HostCommunication\Triggers\Trigger;

/**
 * This controller is only used by tests. It shouldn't be used by any prod code
 */
class TestApiRequestController extends Controller
{
    public function __construct()
    {
        // We need to switch the database for Tests to avoid breaking of the prod database
        DB::setDefaultConnection('testing');
        // we need to keep broadcasting to other hosts that is the test mode
        Trigger::setRequestHeaders([
            'Test-Mode: true',
        ]);
    }

    public function getCache(string $cacheKey): Response
    {
        return response(Cache::store('clustercache')->get($cacheKey));
    }
}