<?php

namespace Sopamo\ClusterCache\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Sopamo\ClusterCache\Models\Host;

class HostFactory extends Factory
{
    protected $model = Host::class;

    public function definition(): array
    {

        return [
            'ip' => fake()->unique()->ipv4(),
            ];
    }
}