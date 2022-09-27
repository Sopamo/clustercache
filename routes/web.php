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
    $apcu = app(\App\ClusterCache\ApcuCache::class);

    logger('Cache exists before saving: ' . $apcu->exists('pages'));
    logger(json_encode($apcu->read('pages')));

    $pages = \App\Models\Page::take(100)
        ->get();

/*    $apcu->write('pages', $pages);*/

    logger('Cache exists after saving: ' . $apcu->exists('pages'));

    return view('welcome');
});
