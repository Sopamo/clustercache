<?php

namespace Sopamo\ClusterCache\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Sopamo\ClusterCache\Database\Factories\CacheEntryFactory;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;
use Sopamo\ClusterCache\Serialization;

class CacheEntry extends Model
{
    use HasFactory;
    /**
     * 4GB is the limit of the length of longtext in MySQL
     */
    const VALUE_LENGTH_LIMIT = 4294967295;
    protected $dates = [
        'created_at',
        'updated_at',
        'locked_at',
    ];
    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return CacheEntryFactory::new();
    }

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Serialization::unserialize($value),
            set: function ($value)  {
                $serializedValue = Serialization::serialize($value);
                if(strlen($serializedValue) > self::VALUE_LENGTH_LIMIT) {
                    throw new CacheEntryValueIsOutOfMemoryException();
                }
                return $serializedValue;
            } ,
        );
    }
}