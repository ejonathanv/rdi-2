<?php

namespace Database\Factories;

use App\Enums\PatrolRunStatus;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrolRun>
 */
class PatrolRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'round_id' => Round::factory(),
            'status' => PatrolRunStatus::InProgress,
            'started_at' => now(),
            'finished_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PatrolRunStatus::Completed,
            'finished_at' => now(),
        ]);
    }
}
