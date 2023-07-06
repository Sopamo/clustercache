<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

use Sopamo\ClusterCache\Models\Host;

class TestConnectionTrigger implements Trigger
{

    public function handle(string $ip, string $cacheKey = null): bool
    {
        $url =  config('clustercache.protocol') . '://' . $ip;
        // Send a GET request from cachecluster1 to cachecluster2
        $ch = curl_init($url); // Use the service name as the hostname
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if ($response === false) {
            return false;
        } else {
            echo 'Response from cachecluster2: ' . $response;
        }

        curl_close($ch);

        return true;
    }
}