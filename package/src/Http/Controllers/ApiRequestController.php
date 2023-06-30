<?php

namespace Sopamo\ClusterCache\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class ApiRequestController extends Controller
{
    public function connectionStatus(): Response
    {
        return response('ok');
    }
}