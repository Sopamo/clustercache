<?php

use Illuminate\Support\Facades\DB;
\Illuminate\Support\Facades\Config::set('database.default', 'testbench');
\Illuminate\Support\Facades\Config::set('database.connections.testbench', [
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'prefix'   => '',
]);
var_dump(DB::connection()->getDriverName());