<?php

namespace Sopamo\ClusterCache\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Sopamo\ClusterCache\Database\Factories\CacheEntryFactory;
use Sopamo\ClusterCache\Exceptions\CacheEntryValueIsOutOfMemoryException;
use Sopamo\ClusterCache\Serialization;
use Sopamo\ClusterCache\TimeHelpers;

/**
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property int $ttl
 * @property Carbon|null $locked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
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
            get: fn($value) => Serialization::unserialize($value),
            set: function ($value) {
                $serializedValue = Serialization::serialize($value);
                if (strlen($serializedValue) > self::VALUE_LENGTH_LIMIT) {
                    throw new CacheEntryValueIsOutOfMemoryException();
                }
                return $serializedValue;
            },
        );
    }

    public function isExpired():bool {
        if(!$this->ttl) {
            return false;
        }

        $nowFromDB = Carbon::createFromFormat('Y-m-d H:i:s', TimeHelpers::getNowFromDB());

        assert($nowFromDB, 'It has to be a Carbon object');

        return $nowFromDB->greaterThan($this->updated_at->addSeconds($this->ttl));

    }
}