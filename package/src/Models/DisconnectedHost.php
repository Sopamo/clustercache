<?php

namespace Sopamo\ClusterCache\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Sopamo\ClusterCache\Database\Factories\DisconnectedHostFactory;

/**
 * @property int $id
 * @property string $from
 * @property string $to
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DisconnectedHost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return DisconnectedHostFactory::new();
    }
}