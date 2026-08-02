<?php

namespace Database\Factories;

use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckpointQuestion>
 */
class CheckpointQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checkpoint_id' => Checkpoint::factory(),
            'body' => fake()->sentence().'?',
            'position' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
