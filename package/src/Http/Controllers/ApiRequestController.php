<?php

namespace Sopamo\ClusterCache\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\HostCommunication\Triggers\Trigger;
use Sopamo\ClusterCache\HostHelpers;
use Sopamo\ClusterCache\LocalCacheManager;
use Sopamo\ClusterCache\MemoryDriver;
use Sopamo\ClusterCache\Models\Host;

class ApiRequestController extends Controller
{
    protected LocalCacheManager $localCacheManager;
    public function __construct(Request $request)
    {
        if($request->hasHeader('Test-Mode')) {
            // the "Test-Mode" header is sent by requests from tests.
            // We need to switch the database for Tests to avoid breaking of the prod database
            DB::setDefaultConnection('testing');
            // we need to keep broadcasting to other hosts that is the test mode
            Trigger::setRequestHeaders([
                'Test-Mode: true',
            ]);
        }

        $this->localCacheManager = app(LocalCacheManager::class);
    }

    public function confirmConnectionStatus(): Response
    {
        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    public function fetchHosts(): Response
    {
        //logger('Fetching hosts in ' . HostHelpers::getHostIp());
        //logger(json_encode(Host::pluck('ip')));
        Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
        //logger('Putting data into local storage: ' . Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip')));
        //logger('Fetching hosts in ' . HostHelpers::getHostIp() . ' from local storage');
        //logger(json_encode(Cache::store('clustercache')->get('clustercache_hosts')));

        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    public function testConnectionToHost(string $hostIp,  HostCommunication $hostCommunication): Response
    {
        logger('testing hosts in ' . HostHelpers::getHostIp());
        logger('Host to test: ' . $hostIp);

        $hostCommunication->trigger(Event::fromInt(Event::$allEvents['TEST_CONNECTION']), $hostIp);

        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }

    /**
     * @throws Exception
     */
    public function callEvent(string $key, int $eventType): Response
    {

        switch ($eventType) {
            case Event::$allEvents['CACHE_KEY_HAS_UPDATED']:
                $this->localCacheManager->delete($key);
                break;
            default:
                throw new Exception('This event does not exist');
        }

        return response(HostHelpers::HOST_REQUEST_RESPONSE);
    }
}