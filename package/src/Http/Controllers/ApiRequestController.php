<?php

namespace Sopamo\ClusterCache\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\Models\Host;

class ApiRequestController extends Controller
{
    public function connectionStatus(): Response
    {
        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    public function fetchHosts(): Response
    {
        Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }
}