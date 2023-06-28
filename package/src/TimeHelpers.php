<?php

namespace Sopamo\ClusterCache;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TimeHelpers
{
    /**
     * Returns the shift between the host and the database server
     * @throws Exception
     */
    public static function getTimeShift(): int
    {
        $nowFromDB = Carbon::createFromFormat('Y-m-d H:i:s', self::getNowFromDB());

        if(!$nowFromDB) {
            throw new Exception('The wrong data format');
        }

        return Carbon::now()->getTimestamp() - $nowFromDB->getTimestamp();
    }

    public static function getNowFromDB(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => DB::select(DB::raw("SELECT strftime('%Y-%m-%d %H:%M:%S','now') as now"))[0]->now,
            default => DB::select(DB::raw('SELECT NOW() as now'))[0]->now,
        };
    }
}