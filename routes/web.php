<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tests-open', function () {
    $start = microtime(true);
    $pages = \App\Models\Page::take(50000)
        ->get();
    $end = microtime(true) - $start;
    logger('Time of DB query: ' . $end);

        $bytes = strlen(serialize($pages)) * 8;

        for($i=0;$i<100;$i++) {
            if( !($shmid=shmop_open($i+1,'n',0660,$bytes)) )
                die('shmop_open failed.');
            $shm_bytes_written = shmop_write($shmid, serialize($pages), 0);

        }

        $totalTime = 0;
        $totalTimeReading = 0;
        for($i=0;$i<100;$i++) {
            $start = microtime(true);
            if( !($shmid=shmop_open($i+1,'a',0660,$bytes)) )
                die('shmop_open failed.');
            $end = microtime(true) - $start;
            $totalTime += $end;
    /*        logger('Time of shmop opening: ' . $end);*/
        $start = microtime(true);
        shmop_read($shmid, 0, $bytes);
        $end = microtime(true) - $start;
        $totalTimeReading += $end;
/*        logger('Time of shmop reading: ' . $end);
        logger('-------------');*/
        shmop_delete($shmid);
    }
    logger('Average of opening: ' . $totalTime / 100);
    logger('Average of reading: ' . $totalTimeReading / 100);

});
Route::get('/tests', function () {
    $apcu = app(\App\ClusterCache\ApcuCache::class);


    $start = microtime(true);
    $pages = \App\Models\Page::take(1000)
        ->get();
    $end = microtime(true) - $start;
    logger('Time of Eloquent fetching: ' . $end);

    $apcu->write('pages', $pages);

    $bytes = strlen(serialize($pages)) * 8;


    if( !($shmid=shmop_open(3,'n',0660,$bytes)) )
        die('shmop_open failed.');
    $shm_bytes_written = shmop_write($shmid, serialize($pages), 0);

    for($i = 0; $i < 100;$i++) {
        $start = microtime(true);
        $apcuPages = $apcu->read('pages');
        $end = microtime(true) - $start;
        logger('Time of APCU_ fetching: #' . $i . ' ' . $end);

        $start = microtime(true);
        $shm_data = shmop_read($shmid, 0, $shm_bytes_written);
        $end2 = microtime(true) - $start;
        logger('Time of Shmop fetching: #' . $i . ' ' . $end2);
        logger('Shmop is slower about: ' . $end2 - $end);
        logger('--------------------------');
    }

    shmop_delete($shmid);

    logger(json_encode($apcuPages));
    logger('-------------');
    logger(json_encode(unserialize($shm_data)));

    return view('welcome');
});
Route::get('/tests-workers-save', function () {
    logger('Process ID ' . getmypid());

    $pages = \App\Models\Page::take(1000)
        ->get();

    $bytes = strlen(serialize($pages)) * 8;

    if( !($shmid=shmop_open(3,'n',0660,$bytes)) )
        die('shmop_open failed.');
    $shm_bytes_written = shmop_write($shmid, serialize($pages), 0);
});

Route::get('/tests-workers-read', function () {
    logger('Process ID ' . getmypid());

    $pages = \App\Models\Page::take(1000)
        ->get();

    $bytes = strlen(serialize($pages)) * 8;

    if( !($shmid=shmop_open(3,'a',0660,$bytes)) ) {
        logger('shmop_open failed');
        die('shmop_open failed.');
    }
    $shm_data = shmop_read($shmid, 0, $bytes);

    logger(strlen($shm_data));
});
