<?php

namespace App\Http\Controllers;

use App\Enums\PatrolVisitOutcome;
use App\Http\Requests\CheckpointScan\MarkAllClearRequest;
use App\Http\Requests\CheckpointScan\StoreCheckpointScanRequest;
use App\Models\Checkpoint;
use App\Models\CheckpointSubmission;
use App\Models\PatrolRun;
use App\Services\PatrolVisitRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckpointScanController extends Controller
{
    public function __construct(private PatrolVisitRecorder $visitRecorder) {}

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

        DB::transaction(function () use ($request, $checkpoint, $patrol): void {
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

            $this->visitRecorder->record(
                $patrol,
                $checkpoint,
                PatrolVisitOutcome::Questionnaire,
                $submission,
            );
        });

        $request->session()->forget('patrol_verified_checkpoint_id');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Respuestas enviadas correctamente.'),
        ]);

        return to_route('checkpoints.scan.complete', $token);
    }

    public function allClear(MarkAllClearRequest $request, string $token): RedirectResponse
    {
        $checkpoint = $request->checkpoint();
        abort_unless($checkpoint->round->is_active, 404);

        $hasQuestions = $checkpoint->questions()->where('is_active', true)->exists();

        if ($hasQuestions) {
            throw ValidationException::withMessages([
                'checkpoint' => __('Este punto tiene cuestionario; debes responderlo.'),
            ]);
        }

        $patrol = $this->requireActivePatrol($request, $checkpoint);

        $this->visitRecorder->record(
            $patrol,
            $checkpoint,
            PatrolVisitOutcome::AllClear,
        );

        $request->session()->forget('patrol_verified_checkpoint_id');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Punto marcado como área sin novedad.'),
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
}
