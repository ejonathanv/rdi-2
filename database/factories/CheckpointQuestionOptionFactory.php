<?php

namespace Database\Factories;

use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckpointQuestionOption>
 */
class CheckpointQuestionOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checkpoint_question_id' => CheckpointQuestion::factory(),
            'label' => fake()->randomElement(['Sí', 'No', 'No sé']),
            'position' => fake()->numberBetween(1, 5),
        ];
    }
}
