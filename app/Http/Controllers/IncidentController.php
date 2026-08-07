<?php

namespace App\Http\Controllers;

use App\Enums\AreaRole;
use App\Http\Requests\Incident\StoreIncidentRequest;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\PatrolRun;
use App\Services\IncidentReporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    public function __construct(private IncidentReporter $reporter) {}

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasGuardRole(), 403);

        $area = $this->resolveGuardArea($request);

        abort_unless($area !== null, 403);

        return Inertia::render('incidents/create', [
            'area' => $area->only(['id', 'name', 'code']),
            'context' => null,
            'store_url' => route('incidents.store'),
            'cancel_url' => route('guard.home'),
        ]);
    }

    public function createFromScan(Request $request, string $token): Response
    {
        $checkpoint = $this->findActiveCheckpoint($token);

        abort_unless($request->user()?->canRespondToCheckpoint($checkpoint), 403);

        $patrol = $this->requireActivePatrol($request, $checkpoint);

        if ($patrol->visits()->where('checkpoint_id', $checkpoint->id)->exists()) {
            return redirect()->route('checkpoints.scan.show', $token);
        }

        return Inertia::render('incidents/create', [
            'area' => $checkpoint->round->area->only(['id', 'name', 'code']),
            'context' => [
                'checkpoint_token' => $checkpoint->token,
                'checkpoint_name' => $checkpoint->name,
                'round_title' => $checkpoint->round->title,
            ],
            'store_url' => route('incidents.store'),
            'cancel_url' => route('checkpoints.scan.show', $token),
        ]);
    }

    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $token = $request->validated('checkpoint_token');

        $patrol = null;
        $checkpoint = null;
        $area = null;

        if (is_string($token) && $token !== '') {
            $checkpoint = $this->findActiveCheckpoint($token);
            abort_unless($user->canRespondToCheckpoint($checkpoint), 403);
            $patrol = $this->requireActivePatrol($request, $checkpoint);
            $area = $checkpoint->round->area;
        } else {
            $area = $this->resolveGuardArea($request);
            abort_unless($area !== null, 403);
        }

        $this->reporter->report(
            guard: $user,
            area: $area,
            message: $request->validated('message'),
            isUrgent: $request->boolean('is_urgent'),
            photos: $this->uploadedPhotos($request),
            patrol: $patrol,
            checkpoint: $checkpoint,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Incidencia registrada.'),
        ]);

        if ($checkpoint && $patrol) {
            return redirect()->route('checkpoints.scan.complete', $checkpoint->token);
        }

        return redirect()->route('guard.home');
    }

    private function resolveGuardArea(Request $request): ?Area
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $currentAreaId = $request->attributes->get('current_area_id')
            ?? $request->session()->get('current_area_id');

        if ($currentAreaId) {
            $area = Area::query()->find((int) $currentAreaId);

            if ($area && ($user->isSuperAdmin() || $user->hasRoleIn($area, AreaRole::Guard) || $user->canManageArea($area))) {
                return $area;
            }
        }

        $guardAreaIds = $user->guardAreaIds();

        if ($guardAreaIds === []) {
            return null;
        }

        return Area::query()->find($guardAreaIds[0]);
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

    private function requireActivePatrol(Request $request, Checkpoint $checkpoint): PatrolRun
    {
        $patrolId = $request->session()->get('active_patrol_run_id');

        $patrol = $patrolId
            ? PatrolRun::query()
                ->whereKey($patrolId)
                ->where('user_id', $request->user()->id)
                ->first()
            : null;

        if (! $patrol || ! $patrol->isInProgress() || $patrol->round_id !== $checkpoint->round_id) {
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
