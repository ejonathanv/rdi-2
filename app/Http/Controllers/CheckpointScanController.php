<?php

namespace App\Http\Controllers;

use App\Enums\PatrolVisitOutcome;
use App\Http\Requests\CheckpointScan\MarkAllClearRequest;
use App\Http\Requests\CheckpointScan\StoreCheckpointScanRequest;
use App\Models\Checkpoint;
use App\Models\CheckpointSubmission;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Services\CheckpointVisitPhotoStore;
use App\Services\PatrolVisitRecorder;
use App\Services\UrgentVisitNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckpointScanController extends Controller
{
    public function __construct(
        private PatrolVisitRecorder $visitRecorder,
        private CheckpointVisitPhotoStore $photoStore,
        private UrgentVisitNotifier $urgentNotifier,
    ) {}

    public function show(Request $request, string $token): Response
    {
        $checkpoint = $this->findActiveCheckpoint($token);

        abort_unless($request->user()?->canRespondToCheckpoint($checkpoint), 403);

        $patrol = $this->activePatrolFor($request, $checkpoint);

        $questions = $checkpoint->questions()
            ->where('is_active', true)
            ->with(['options' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->get();

        $alreadyReviewed = $patrol
            ? $patrol->visits()->where('checkpoint_id', $checkpoint->id)->exists()
            : false;

        return Inertia::render('checkpoints/scan', [
            'area' => $checkpoint->round->area->only(['id', 'name', 'code']),
            'round' => $checkpoint->round->only(['id', 'title']),
            'checkpoint' => [
                'id' => $checkpoint->id,
                'name' => $checkpoint->name,
                'instructions' => $checkpoint->instructions,
                'token' => $checkpoint->token,
            ],
            'questions' => $questions->map(fn ($question) => [
                'id' => $question->id,
                'body' => $question->body,
                'position' => $question->position,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'position' => $option->position,
                ])->values()->all(),
            ])->values()->all(),
            'patrol' => $patrol ? [
                'id' => $patrol->id,
                'already_reviewed' => $alreadyReviewed,
            ] : null,
        ]);
    }

    public function store(StoreCheckpointScanRequest $request, string $token): RedirectResponse
    {
        $checkpoint = $this->findActiveCheckpoint($token);
        $patrol = $this->requireActivePatrol($request, $checkpoint);

        if ($patrol->visits()->where('checkpoint_id', $checkpoint->id)->exists()) {
            throw ValidationException::withMessages([
                'checkpoint' => __('Este punto ya fue revisado en el recorrido actual.'),
            ]);
        }

        $visit = DB::transaction(function () use ($request, $checkpoint, $patrol): PatrolCheckpointVisit {
            $submission = CheckpointSubmission::query()->create([
                'checkpoint_id' => $checkpoint->id,
                'user_id' => $request->user()->id,
                'patrol_run_id' => $patrol->id,
            ]);

            foreach ($request->validated('answers') as $questionId => $optionId) {
                $submission->answers()->create([
                    'checkpoint_question_id' => (int) $questionId,
                    'checkpoint_question_option_id' => (int) $optionId,
                ]);
            }

            $visit = $this->visitRecorder->record(
                $patrol,
                $checkpoint,
                PatrolVisitOutcome::Questionnaire,
                $submission,
                $request->boolean('is_urgent'),
                $request->input('urgent_notes'),
            );

            $this->photoStore->store($visit, $this->uploadedPhotos($request));

            return $visit;
        });

        $this->urgentNotifier->notify($visit);

        $request->session()->forget('patrol_verified_checkpoint_id');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->boolean('is_urgent')
                ? __('Respuestas enviadas. Se notificó al contacto asignado.')
                : __('Respuestas enviadas correctamente.'),
        ]);

        return to_route('checkpoints.scan.complete', $token);
    }

    public function allClear(MarkAllClearRequest $request, string $token): RedirectResponse
    {
        $checkpoint = $request->checkpoint();
        abort_unless($checkpoint->round->is_active, 404);

        $patrol = $this->requireActivePatrol($request, $checkpoint);

        $visit = DB::transaction(function () use ($request, $checkpoint, $patrol): PatrolCheckpointVisit {
            $visit = $this->visitRecorder->record(
                $patrol,
                $checkpoint,
                PatrolVisitOutcome::AllClear,
                isUrgent: $request->boolean('is_urgent'),
                urgentNotes: $request->input('urgent_notes'),
            );

            $this->photoStore->store($visit, $this->uploadedPhotos($request));

            return $visit;
        });

        $this->urgentNotifier->notify($visit);

        $request->session()->forget('patrol_verified_checkpoint_id');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->boolean('is_urgent')
                ? __('Punto marcado como urgente. Se notificó al contacto asignado.')
                : __('Punto marcado como área sin novedad.'),
        ]);

        return to_route('checkpoints.scan.complete', $token);
    }

    public function complete(Request $request, string $token): Response
    {
        $checkpoint = $this->findActiveCheckpoint($token);

        abort_unless($request->user()?->canRespondToCheckpoint($checkpoint), 403);

        $patrol = $this->activePatrolFor($request, $checkpoint)?->fresh();

        return Inertia::render('checkpoints/scan-complete', [
            'checkpoint' => [
                'name' => $checkpoint->name,
                'token' => $checkpoint->token,
            ],
            'round' => $checkpoint->round->only(['title']),
            'patrol' => $patrol ? [
                'id' => $patrol->id,
                'status' => $patrol->status->value,
                'finished_at' => $patrol->finished_at?->toIso8601String(),
                'duration_seconds' => $patrol->durationInSeconds(),
            ] : null,
        ]);
    }

    private function findActiveCheckpoint(string $token): Checkpoint
    {
        $checkpoint = Checkpoint::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->with(['round.area'])
            ->firstOrFail();

        abort_unless($checkpoint->round->is_active, 404);

        return $checkpoint;
    }

    private function activePatrolFor(Request $request, Checkpoint $checkpoint): ?PatrolRun
    {
        $patrolId = $request->session()->get('active_patrol_run_id');

        if (! $patrolId) {
            return null;
        }

        $patrol = PatrolRun::query()
            ->whereKey($patrolId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $patrol || $patrol->round_id !== $checkpoint->round_id) {
            return null;
        }

        return $patrol;
    }

    private function requireActivePatrol(Request $request, Checkpoint $checkpoint): PatrolRun
    {
        $patrol = $this->activePatrolFor($request, $checkpoint);

        if (! $patrol || ! $patrol->isInProgress()) {
            throw ValidationException::withMessages([
                'patrol' => __('Debes iniciar un recorrido antes de revisar este punto.'),
            ]);
        }

        return $patrol;
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
