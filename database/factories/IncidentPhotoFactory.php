<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentPhoto>
 */
class IncidentPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'path' => 'incident-evidence/1/01.jpg',
            'position' => 1,
        ];
    }
}
