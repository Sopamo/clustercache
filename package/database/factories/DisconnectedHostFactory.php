<?php

namespace Sopamo\ClusterCache\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Sopamo\ClusterCache\Models\DisconnectedHost;

class DisconnectedHostFactory extends Factory
{
    protected $model = DisconnectedHost::class;

    public function definition(): array
    {

        return [];
    }
}