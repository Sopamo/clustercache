<?php

namespace App\ClusterCache\Controllers;

use Illuminate\Http\JsonResponse;

class HostController
{
    public function connectionStatus(): JsonResponse
    {
        // TO DO;
        return response()->json([]);
    }

    public function fetchHosts(): void
    {
        // TO DO
    }
}