<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Carbon;
use Sopamo\ClusterCache\Exceptions\NotFoundLocalCacheKeyException;
use Sopamo\ClusterCache\HostCommunication\Event;
use Sopamo\ClusterCache\HostCommunication\HostCommunication;
use Sopamo\ClusterCache\Models\DisconnectedHost;
use Sopamo\ClusterCache\Models\Host;

class HostInNetwork
{
    public static function join():void {
        Host::updateOrCreate([
            'ip' => self::getHostIp()
        ]);
        CachedHosts::refresh();
        //logger('Putting cache in host ' . HostHelpers::getHostIp() . ': ' . Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip')));
        //Cache::store('clustercache')->put('clustercache_hosts', Host::pluck('ip'));
        //logger('Fetching hosts in init() in ' . HostHelpers::getHostIp() . ' from local storage');
        //logger(json_encode(Cache::store('clustercache')->get('clustercache_hosts')));

        app(HostCommunication::class)->triggerAll(Event::fromInt(Event::$allEvents['FETCH_HOSTS']));
    }

    public static function leave():void {
        Host::where('ip', self::getHostIp())->delete();
        CachedHosts::refresh();
        DisconnectedHost::where('from', self::getHostIp())->orWhere('to', self::getHostIp())->delete();
        app(HostCommunication::class)->triggerAll(Event::fromInt(Event::$allEvents['FETCH_HOSTS']));
    }

    public static function isConnected(): bool {
        /** @var LocalCacheManager $localCacheManager */
        $localCacheManager = app(LocalCacheManager::class);

        try{
            return !!$localCacheManager->get(CacheKey::INTERNAL_USED_KEYS['isConnected']);
        }  catch (NotFoundLocalCacheKeyException) {
            return true;
        }
    }

    public static function getHostIp():string {
        $ips = exec('hostname -i');

        if(!$ips) {
            return '';
        }

        return explode(' ', $ips)[0];
    }

    public static function markAsConnected(): void {
        self::updateIsConnectedFlag(true);
    }

    public static function markAsDisconnected(): void {
        self::updateIsConnectedFlag(false);
    }

    private static function updateIsConnectedFlag(bool $isConnected): void
    {
        /** @var LocalCacheManager $localCacheManager */
        $localCacheManager = app(LocalCacheManager::class);
        $localCacheManager->put(CacheKey::INTERNAL_USED_KEYS['isConnected'], $isConnected, Carbon::now()->getTimestamp());
    }
}