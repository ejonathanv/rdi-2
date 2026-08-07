<?php

namespace App\Http\Controllers;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Http\Requests\PanicAlert\StorePanicAlertRequest;
use App\Models\Area;
use App\Models\PatrolRun;
use App\Services\PanicAlertReporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PanicAlertController extends Controller
{
    public function __construct(private PanicAlertReporter $reporter) {}

    public function store(StorePanicAlertRequest $request): RedirectResponse
    {
        $user = $request->user();
        $area = $this->resolveGuardArea($request);

        abort_unless($area !== null, 403);

        $patrol = $this->resolveActivePatrol($request, $user->id);

        $this->reporter->report($user, $area, $patrol);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Alerta de pánico enviada a los contactos del área.'),
        ]);

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

    private function resolveActivePatrol(Request $request, int $userId): ?PatrolRun
    {
        $patrolId = $request->session()->get('active_patrol_run_id');

        if (! $patrolId) {
            return null;
        }

        $patrol = PatrolRun::query()
            ->whereKey($patrolId)
            ->where('user_id', $userId)
            ->where('status', PatrolRunStatus::InProgress)
            ->first();

        return $patrol;
    }
}
