<?php
Route::prefix('clustercache/api')->group(function () {
    Route::get('/connection-status', '\Sopamo\ClusterCache\Http\Controllers\ApiRequestController@connectionStatus');
    Route::get('/fetch-hosts', '\Sopamo\ClusterCache\Http\Controllers\ApiRequestController@fetchHosts');
});