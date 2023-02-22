<?php

namespace Sopamo\ClusterCache\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Sopamo\ClusterCache\Models\CacheEntry;

class CacheEntryFactory extends Factory
{
    protected $model = CacheEntry::class;

    public function definition(): array
    {

        return [
            'key' => fake()->unique()->text(),
            'value' => fake()->unique()->text(),
            ];
    }
}