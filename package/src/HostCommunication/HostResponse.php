<?php

namespace Sopamo\ClusterCache\HostCommunication;

class HostResponse
{
    const HOST_REQUEST_RESPONSE = 'ok';
    const HOST_HAS_BEEN_INFORMED = 'Host has been informed';
    const HOST_HAS_NOT_BEEN_INFORMED = 'Host has not been informed';

    public function __construct(protected string $response)
    {
    }

    public function getResponse():string
    {
        return $this->response;
    }

    public function wasSuccessful():bool
    {
        return in_array($this->response, [
            self::HOST_REQUEST_RESPONSE,
            self::HOST_HAS_BEEN_INFORMED,
            self::HOST_HAS_NOT_BEEN_INFORMED
        ]);
    }
}