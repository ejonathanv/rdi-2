<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'user_id' => User::factory(),
            'patrol_run_id' => null,
            'checkpoint_id' => null,
            'message_raw' => fake()->sentence(),
            'message_cleaned' => null,
            'incident_category_id' => null,
            'is_urgent' => false,
            'categorized_at' => null,
        ];
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_urgent' => true,
        ]);
    }
}
