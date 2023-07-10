<?php

namespace Sopamo\ClusterCache\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\EventLockInformation;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\LockingMechanisms\EventLocker;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\Host;

class ApiRequestController extends Controller
{
    public function __construct()
    {
        EventLockInformation::setMemoryDriver(MemoryDriver::fromString(config('clustercache.driver')));
    }

    public function confirmConnectionStatus(): Response
    {
        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    public function fetchHosts(): Response
    {
        Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    /**
     * @throws Exception
     */
    public function callEvent(string $key, int $eventType): Response
    {
        $eventLocker = new EventLocker();

        switch ($eventType) {
            case Event::$allEvents['CACHE_KEY_IS_UPDATING']:
                $eventLocker->acquire($key, $eventType);
                break;
            case Event::$allEvents['CACHE_KEY_HAS_UPDATED']:
            case Event::$allEvents['CACHE_KEY_UPDATING_HAS_CANCELED']:
                $eventLocker->release($key);
                break;
            default:
                throw new Exception('This event does not exist');
        }

        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }
}