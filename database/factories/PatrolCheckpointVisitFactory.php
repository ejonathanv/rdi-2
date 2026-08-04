<?php

namespace Database\Factories;

use App\Enums\PatrolVisitOutcome;
use App\Models\Checkpoint;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrolCheckpointVisit>
 */
class PatrolCheckpointVisitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patrol_run_id' => PatrolRun::factory(),
            'checkpoint_id' => Checkpoint::factory(),
            'reviewed_at' => now(),
            'outcome' => PatrolVisitOutcome::AllClear,
            'checkpoint_submission_id' => null,
        ];
    }
}
