<?php

namespace Sopamo\ClusterCache\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;

class CacheEntry extends Model
{
    /**
     * 4GB is the limit of the length of longtext in MySQL
     */
    const VALUE_LENGTH_LIMIT = 4294967295;
    protected $guarded = [];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => unserialize($value),
            set: function ($value)  {
                $serializedValue = serialize($value);
                if(strlen($serializedValue) > self::VALUE_LENGTH_LIMIT) {
                    throw new CacheEntryValueIsOutOfMemoryException();
                }
                return $serializedValue;
            } ,
        );
    }
}