<?php
Route::prefix('clustercache/api')->group(function () {
    Route::post('/connection-status', '\Sopamo\ClusterCache\Http\Controllers\ApiRequestController@connectionStatus');
});