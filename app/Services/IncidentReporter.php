<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Enums\PatrolVisitOutcome;
use App\Jobs\CategorizeIncident;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\PatrolRun;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class IncidentReporter
{
    public function __construct(
        private IncidentPhotoStore $photoStore,
        private PatrolVisitRecorder $visitRecorder,
    ) {}

    /**
     * @param  list<UploadedFile>  $photos
     */
    public function report(
        User $guard,
        Area $area,
        string $message,
        bool $isUrgent,
        array $photos = [],
        ?PatrolRun $patrol = null,
        ?Checkpoint $checkpoint = null,
        ?CarbonInterface $occurredAt = null,
        ?string $clientUuid = null,
        ?string $visitClientUuid = null,
    ): Incident {
        $incident = DB::transaction(function () use ($guard, $area, $message, $isUrgent, $photos, $patrol, $checkpoint, $occurredAt, $clientUuid, $visitClientUuid) {
            $incident = Incident::query()->create([
                'client_uuid' => $clientUuid,
                'area_id' => $area->id,
                'user_id' => $guard->id,
                'patrol_run_id' => $patrol?->id,
                'checkpoint_id' => $checkpoint?->id,
                'message_raw' => $message,
                'is_urgent' => $isUrgent,
                'status' => IncidentStatus::Nueva,
                'occurred_at' => $occurredAt,
            ]);

            $this->photoStore->store($incident, $photos);

            if ($patrol && $checkpoint) {
                $this->visitRecorder->record(
                    patrol: $patrol,
                    checkpoint: $checkpoint,
                    outcome: PatrolVisitOutcome::Incident,
                    isUrgent: $isUrgent,
                    urgentNotes: $isUrgent ? $message : null,
                    reviewedAt: $occurredAt,
                    clientUuid: $visitClientUuid,
                );
            }

            return $incident;
        });

        CategorizeIncident::dispatch($incident);

        return $incident->fresh(['category', 'photos', 'checkpoint']) ?? $incident;
    }
}
