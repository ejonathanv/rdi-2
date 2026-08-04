<?php

namespace Database\Factories;

use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolCheckpointVisitPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrolCheckpointVisitPhoto>
 */
class PatrolCheckpointVisitPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patrol_checkpoint_visit_id' => PatrolCheckpointVisit::factory(),
            'path' => 'patrol-evidence/'.$this->faker->uuid().'/01.jpg',
            'position' => 1,
        ];
    }
}
