<?php

namespace Sopamo\ClusterCache\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Sopamo\ClusterCache\CacheManager;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\Host;

class ApiRequestController extends Controller
{
    protected CacheManager $cacheManager;
    public function __construct()
    {
        $this->cacheManager = app(CacheManager::class, ['memoryDriver' => MemoryDriver::fromString(config('clustercache.driver'))]);
    }

    public function confirmConnectionStatus(): Response
    {
        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    public function fetchHosts(): Response
    {
        logger('Fetching hosts in ' . HostHelpers::getHostIp());
        logger(json_encode(Host::pluck('ip')));
        logger('Putting data into local storage: ' . Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip')));
        logger('Fetching hosts in ' . HostHelpers::getHostIp() . ' from local storage');
        logger(json_encode(Cache::store('clustercache')->get('clustercache_hosts')));
        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    /**
     * @throws Exception
     */
    public function callEvent(string $key, int $eventType): Response
    {

        switch ($eventType) {
            case Event::$allEvents['CACHE_KEY_HAS_UPDATED']:
                $this->cacheManager->deleteFromLocalCache($key);
                break;
            default:
                throw new Exception('This event does not exist');
        }

        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }
}