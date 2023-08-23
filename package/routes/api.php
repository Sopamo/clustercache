<?php
Route::prefix('clustercache/api')->group(function () {
    Route::get('/connection-status', '\Sopamo\ClusterCache\Http\Controllers\ApiRequestController@confirmConnectionStatus');
    Route::get('/fetch-hosts', '\Sopamo\ClusterCache\Http\Controllers\ApiRequestController@fetchHosts');
    Route::get('/call-event/{key}/{eventType}', '\Sopamo\ClusterCache\Http\Controllers\ApiRequestController@callEvent');
});