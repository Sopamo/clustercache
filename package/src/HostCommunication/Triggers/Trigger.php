<?php

namespace Sopamo\ClusterCache\HostCommunication\Triggers;

abstract class Trigger
{
    protected static array $requestHeaders = [];
    public static function setRequestHeaders(array $requestHeaders):void {
        static::$requestHeaders = $requestHeaders;
    }
    protected function executeRequest(string $url): string|bool
    {
        $ch = curl_init($url);

        if(!$ch) {
            return false;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, static::$requestHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}