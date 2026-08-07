<?php

namespace Database\Factories;

use App\Enums\IncidentStatus;
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
            'status' => IncidentStatus::Nueva,
            'assigned_to_id' => null,
            'acknowledged_at' => null,
            'resolved_by_id' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
            'categorized_at' => null,
        ];
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_urgent' => true,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::EnAtencion,
            'assigned_to_id' => User::factory(),
            'acknowledged_at' => now()->subMinutes(10),
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IncidentStatus::Resuelta,
            'assigned_to_id' => User::factory(),
            'acknowledged_at' => now()->subHour(),
            'resolved_by_id' => User::factory(),
            'resolved_at' => now()->subMinutes(5),
            'resolution_notes' => 'Cerrada en prueba.',
        ]);
    }
}
