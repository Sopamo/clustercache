<?php
$memoryKey = ShmopDriver::generateMemoryKey();
ShmopDriver::put($memoryKey, 'test', 4 + ShmopDriver::METADATA_LENGTH_IN_BYTES);
ShmopDriver::get($memoryKey, 4 + ShmopDriver::METADATA_LENGTH_IN_BYTES);
ShmopDriver::delete($memoryKey, 4 + ShmopDriver::METADATA_LENGTH_IN_BYTES);
ShmopDriver::get($memoryKey, 4 + ShmopDriver::METADATA_LENGTH_IN_BYTES);

$shmopDriver = new ShmopDriver();
MetaInformation::init($shmopDriver);
MetaInformation::get('key');
MetaInformation::put('key', ['value']);
MetaInformation::get('key');
MetaInformation::delete('key');
MetaInformation::get('key');
MetaInformation::get('cache key2');

$cacheEntry = CacheEntry::first();
$nowFromDB = Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i:s',  DBLocker::getNowFromDB());
$difference = Illuminate\Support\Carbon::now()->timestamp - $nowFromDB->timestamp;
$updatedAt = $cacheEntry->updated_at->timestamp + Illuminate\Support\Carbon::now()->timestamp - $nowFromDB->timestamp;


$driver = Sopamo\ClusterCache\MemoryDriver::fromString('SHMOP');
$cacheManager = new Sopamo\ClusterCache\CacheManager($driver);
$cacheManager->put('cache key', 'value');
$cacheManager->get('cache key');
$cacheManager->put('cache key', 'value xyzxyz xyz');
$cacheManager->get('cache key');
$cacheManager->delete('cache key');
$cacheManager->get('cache key');
MetaInformation::get('cache key');
CacheManager::put('cache key2', 'value', 5);
CacheManager::get('cache key2');

$pages = Page::take(100)->get();
CacheManager::put('cache key', $pages);
MetaInformation::get('cache key');
CacheManager::get('cache key');
CacheManager::delete('cache key');

$value = Cache::store('clustercache')->get('foo');
Cache::store('clustercache')->put('foo', 'test5');
$value = Cache::store('clustercache')->get('foo');


$driver = Sopamo\ClusterCache\MemoryDriver::fromString('SHMOP');
$cacheManager = new Sopamo\ClusterCache\CacheManager($driver);
$cacheManager->put('hosts', \Sopamo\ClusterCache\Models\Host::limit(2)->pluck('ip'));
$cacheManager->get('hosts');
$cacheManager->put('hosts', \Sopamo\ClusterCache\Models\Host::pluck('ip'));
$cacheManager->get('hosts');

$cacheManager->put('clusterCache_:clustercache_hosts', \Sopamo\ClusterCache\Models\Host::pluck('ip'));
$cacheManager->get('clusterCache_:clustercache_hosts');

Cache::store('clustercache')->get('clustercache_hosts');

$start = microtime(true);
Cache::store('clustercache')->put('foo', 'test5');
echo microtime(true) - $start;
echo 'done';

$start = microtime(true);
Cache::store('clustercache')->get('foo');
echo microtime(true) - $start;
echo 'done';