<?php

namespace Sopamo\ClusterCache\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Sopamo\ClusterCache\Database\Factories\DisconnectedHostFactory;

class DisconnectedHost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return DisconnectedHostFactory::new();
    }
}