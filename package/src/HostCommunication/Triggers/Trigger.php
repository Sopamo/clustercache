<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

abstract class Trigger
{
    protected function executeRequest(string $url): string|bool
    {
        $ch = curl_init($url);

        if(!$ch) {
            return false;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}