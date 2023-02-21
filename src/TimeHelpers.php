<?php

namespace Sopamo\ClusterCache;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimeHelpers
{
    public static function getNowFromDB():string {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => DB::select(DB::raw("SELECT strftime('%Y-%m-%d %H:%M:%S','now') as now"))[0]->now,
            default => DB::select(DB::raw('SELECT NOW() as now'))[0]->now,
        };
    }

    /**
     * Returns the shift between the host and the database server
     */
    public static function getTimeShift():int {
        $nowFromDB = Carbon::createFromFormat('Y-m-d H:i:s',  self::getNowFromDB());

        return Carbon::now()->timestamp - $nowFromDB->timestamp;
    }
}