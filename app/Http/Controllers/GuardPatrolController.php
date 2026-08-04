<?php

namespace App\Http\Controllers;

use App\Enums\PatrolRunStatus;
use App\Http\Requests\GuardPatrol\StartPatrolRequest;
use App\Models\Checkpoint;
use App\Models\PatrolRun;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GuardPatrolController extends Controller
{
    public function start(StartPatrolRequest $request, Round $round): RedirectResponse
    {
        $user = $request->user();

        $active = PatrolRun::query()
            ->where('user_id', $user->id)
            ->where('status', PatrolRunStatus::InProgress)
            ->first();

        if ($active && $active->round_id !== $round->id) {
            throw ValidationException::withMessages([
                'round' => __('Ya tienes un recorrido en curso. Termínalo antes de iniciar otro.'),
            ]);
        }

        $patrol = $active ?? PatrolRun::query()->create([
            'user_id' => $user->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::InProgress,
            'started_at' => now(),
        ]);

        $request->session()->put('active_patrol_run_id', $patrol->id);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $active
                ? __('Reanudaste el recorrido en curso.')
                : __('Recorrido iniciado.'),
        ]);

        return to_route('guard.patrols.show', $patrol);
    }

    public function show(Request $request, PatrolRun $patrol): Response
    {
        $user = $request->user();
        abort_unless($user?->id === $patrol->user_id, 403);
        abort_unless($user->hasGuardRole(), 403);

        $patrol->load([
            'round.area:id,name',
            'round.checkpoints' => fn ($query) => $query->where('is_active', true)->orderBy('position')->withCount([
                'questions as active_questions_count' => fn ($q) => $q->where('is_active', true),
            ]),
            'visits',
        ]);

        abort_unless(in_array($patrol->round->area_id, $user->guardAreaIds(), true), 403);

        $visitsByCheckpoint = $patrol->visits->keyBy('checkpoint_id');

        $request->session()->put('active_patrol_run_id', $patrol->id);

        return Inertia::render('guard/patrols/show', [
            'patrol' => [
                'id' => $patrol->id,
                'status' => $patrol->status->value,
                'started_at' => $patrol->started_at->toIso8601String(),
                'finished_at' => $patrol->finished_at?->toIso8601String(),
                'duration_seconds' => $patrol->durationInSeconds(),
                'round' => [
                    'id' => $patrol->round->id,
                    'title' => $patrol->round->title,
                    'instructions' => $patrol->round->instructions,
                    'area' => [
                        'id' => $patrol->round->area->id,
                        'name' => $patrol->round->area->name,
                    ],
                ],
                'checkpoints' => $patrol->round->checkpoints->map(function ($checkpoint) use ($visitsByCheckpoint) {
                    $visit = $visitsByCheckpoint->get($checkpoint->id);

                    return [
                        'id' => $checkpoint->id,
                        'name' => $checkpoint->name,
                        'instructions' => $checkpoint->instructions,
                        'position' => $checkpoint->position,
                        'token' => $checkpoint->token,
                        'questions_count' => $checkpoint->active_questions_count,
                        'reviewed' => $visit !== null,
                        'reviewed_at' => $visit?->reviewed_at->toIso8601String(),
                        'outcome' => $visit?->outcome->value,
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function verifyCheckpoint(Request $request, PatrolRun $patrol, Checkpoint $checkpoint): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->id === $patrol->user_id, 403);
        abort_unless($patrol->isInProgress(), 422);
        abort_unless($checkpoint->round_id === $patrol->round_id, 403);
        abort_unless($checkpoint->is_active, 404);

        $token = (string) $request->validate([
            'token' => ['required', 'string'],
        ])['token'];

        $scannedToken = $this->extractToken($token);

        if (! hash_equals($checkpoint->token, $scannedToken)) {
            throw ValidationException::withMessages([
                'token' => __('El QR no corresponde a este punto de revisión.'),
            ]);
        }

        $request->session()->put('active_patrol_run_id', $patrol->id);
        $request->session()->put('patrol_verified_checkpoint_id', $checkpoint->id);

        return to_route('checkpoints.scan.show', $checkpoint->token);
    }

    private function extractToken(string $value): string
    {
        $value = trim($value);

        if (preg_match('#/scan/([^/?]+)#', $value, $matches) === 1) {
            return urldecode($matches[1]);
        }

        return $value;
    }
}
