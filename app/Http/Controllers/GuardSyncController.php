<?php

namespace App\Http\Controllers;

use App\Enums\PatrolRunStatus;
use App\Enums\PatrolVisitOutcome;
use App\Http\Requests\GuardSync\SyncIncidentRequest;
use App\Http\Requests\GuardSync\SyncPanicAlertRequest;
use App\Http\Requests\GuardSync\SyncPatrolRequest;
use App\Http\Requests\GuardSync\SyncVisitRequest;
use App\Models\CheckpointSubmission;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Services\CheckpointVisitPhotoStore;
use App\Services\IncidentReporter;
use App\Services\PanicAlertReporter;
use App\Services\PatrolVisitRecorder;
use App\Services\UrgentVisitNotifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class GuardSyncController extends Controller
{
    public function patrol(SyncPatrolRequest $request): JsonResponse
    {
        if ($existing = $request->existingPatrol()) {
            return $this->synced($existing->id, duplicate: true);
        }

        try {
            $patrol = PatrolRun::query()->create([
                'client_uuid' => $request->validated('client_uuid'),
                'user_id' => $request->user()->id,
                'round_id' => (int) $request->validated('round_id'),
                'status' => PatrolRunStatus::InProgress,
                'started_at' => $request->startedAt(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->synced((int) $request->existingPatrol()?->id, duplicate: true);
        }

        return $this->synced($patrol->id);
    }

    public function visit(
        SyncVisitRequest $request,
        PatrolVisitRecorder $visitRecorder,
        CheckpointVisitPhotoStore $photoStore,
        UrgentVisitNotifier $urgentNotifier,
    ): JsonResponse {
        if ($existing = $request->existingVisit()) {
            return $this->synced($existing->id, duplicate: true);
        }

        $patrol = $request->patrol();
        $checkpoint = $request->checkpoint();

        try {
            $visit = DB::transaction(function () use ($request, $patrol, $checkpoint, $visitRecorder, $photoStore): PatrolCheckpointVisit {
                $submission = null;

                if ($request->outcome() === PatrolVisitOutcome::Questionnaire) {
                    $submission = CheckpointSubmission::query()->create([
                        'checkpoint_id' => $checkpoint->id,
                        'user_id' => $request->user()->id,
                        'patrol_run_id' => $patrol->id,
                    ]);

                    foreach ($request->validated('answers', []) as $questionId => $optionId) {
                        $submission->answers()->create([
                            'checkpoint_question_id' => (int) $questionId,
                            'checkpoint_question_option_id' => (int) $optionId,
                        ]);
                    }
                }

                $visit = $visitRecorder->record(
                    patrol: $patrol,
                    checkpoint: $checkpoint,
                    outcome: $request->outcome(),
                    submission: $submission,
                    isUrgent: $request->boolean('is_urgent'),
                    urgentNotes: $request->input('urgent_notes'),
                    reviewedAt: $request->reviewedAt(),
                    clientUuid: $request->validated('client_uuid'),
                );

                $photoStore->store($visit, $this->uploadedPhotos($request));

                return $visit;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->synced((int) $request->existingVisit()?->id, duplicate: true);
        }

        $urgentNotifier->notify($visit);

        return $this->synced($visit->id, [
            'patrol_status' => $patrol->fresh()?->status->value,
        ]);
    }

    public function incident(SyncIncidentRequest $request, IncidentReporter $reporter): JsonResponse
    {
        if ($existing = $request->existingIncident()) {
            return $this->synced($existing->id, duplicate: true);
        }

        try {
            $incident = $reporter->report(
                guard: $request->user(),
                area: $request->area(),
                message: $request->validated('message'),
                isUrgent: $request->boolean('is_urgent'),
                photos: $this->uploadedPhotos($request),
                patrol: $request->patrol(),
                checkpoint: $request->checkpoint(),
                occurredAt: $request->occurredAt(),
                clientUuid: $request->validated('client_uuid'),
                visitClientUuid: $request->validated('visit_client_uuid'),
            );
        } catch (UniqueConstraintViolationException) {
            return $this->synced((int) $request->existingIncident()?->id, duplicate: true);
        }

        return $this->synced($incident->id, [
            'patrol_status' => $request->patrol()?->fresh()?->status->value,
        ]);
    }

    public function panic(SyncPanicAlertRequest $request, PanicAlertReporter $reporter): JsonResponse
    {
        if ($existing = $request->existingAlert()) {
            return $this->synced($existing->id, duplicate: true);
        }

        try {
            $alert = $reporter->report(
                guard: $request->user(),
                area: $request->area(),
                patrol: $request->patrol(),
                occurredAt: $request->occurredAt(),
                clientUuid: $request->validated('client_uuid'),
            );
        } catch (UniqueConstraintViolationException) {
            return $this->synced((int) $request->existingAlert()?->id, duplicate: true);
        }

        return $this->synced($alert->id);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function synced(int $id, array $extra = [], bool $duplicate = false): JsonResponse
    {
        return response()->json([
            'id' => $id,
            'duplicate' => $duplicate,
            ...$extra,
        ], $duplicate ? 200 : 201);
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedPhotos(Request $request): array
    {
        $photos = $request->file('photos', []);

        if (! is_array($photos)) {
            return $photos ? [$photos] : [];
        }

        return array_values(array_filter($photos));
    }
}
