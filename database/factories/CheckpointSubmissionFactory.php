<?php

namespace Database\Factories;

use App\Models\Checkpoint;
use App\Models\CheckpointSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckpointSubmission>
 */
class CheckpointSubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checkpoint_id' => Checkpoint::factory(),
            'user_id' => User::factory(),
        ];
    }
}
