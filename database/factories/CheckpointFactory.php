<?php

namespace Database\Factories;

use App\Models\Checkpoint;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Checkpoint>
 */
class CheckpointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'round_id' => Round::factory(),
            'name' => 'Punto '.fake()->unique()->word(),
            'instructions' => fake()->sentence(),
            'position' => fake()->numberBetween(0, 20),
            'token' => (string) Str::uuid(),
            'is_active' => true,
        ];
    }
}
