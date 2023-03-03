<?php

namespace Sopamo\ClusterCache;

class Serialization
{
    public static function serialize($value): ?string
    {
        if(function_exists('igbinary_serialize')) {
            return igbinary_serialize($value);
        }
        return serialize($value);
    }
    public static function unserialize($value): mixed
    {
        if(function_exists('igbinary_unserialize')) {
            return igbinary_unserialize($value);
        }
        return unserialize($value);
    }
}