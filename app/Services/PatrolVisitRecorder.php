<?php

namespace App\Services;

use App\Enums\PatrolRunStatus;
use App\Enums\PatrolVisitOutcome;
use App\Models\Checkpoint;
use App\Models\CheckpointSubmission;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatrolVisitRecorder
{
    public function record(
        PatrolRun $patrol,
        Checkpoint $checkpoint,
        PatrolVisitOutcome $outcome,
        ?CheckpointSubmission $submission = null,
    ): PatrolCheckpointVisit {
        abort_unless($patrol->isInProgress(), 422);
        abort_unless($checkpoint->round_id === $patrol->round_id, 403);

        if ($patrol->visits()->where('checkpoint_id', $checkpoint->id)->exists()) {
            throw ValidationException::withMessages([
                'checkpoint' => __('Este punto ya fue revisado en el recorrido actual.'),
            ]);
        }

        return DB::transaction(function () use ($patrol, $checkpoint, $outcome, $submission) {
            $visit = PatrolCheckpointVisit::query()->create([
                'patrol_run_id' => $patrol->id,
                'checkpoint_id' => $checkpoint->id,
                'reviewed_at' => now(),
                'outcome' => $outcome,
                'checkpoint_submission_id' => $submission?->id,
            ]);

            $this->completePatrolIfDone($patrol->fresh(['visits', 'round.checkpoints']));

            return $visit;
        });
    }

    public function completePatrolIfDone(PatrolRun $patrol): void
    {
        if (! $patrol->isInProgress()) {
            return;
        }

        $activeCheckpointIds = $patrol->round
            ->checkpoints()
            ->where('is_active', true)
            ->pluck('id');

        if ($activeCheckpointIds->isEmpty()) {
            return;
        }

        $visitedCount = $patrol->visits()
            ->whereIn('checkpoint_id', $activeCheckpointIds)
            ->count();

        if ($visitedCount < $activeCheckpointIds->count()) {
            return;
        }

        $patrol->update([
            'status' => PatrolRunStatus::Completed,
            'finished_at' => now(),
        ]);
    }
}
