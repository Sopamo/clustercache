<?php

namespace Sopamo\ClusterCache\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HostFactory extends Factory
{

    public function definition(): array
    {
        return [
            'ip' => fake()->ipv4(),
            ];
    }
}