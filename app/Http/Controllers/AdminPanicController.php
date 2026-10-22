<?php

namespace App\Http\Controllers;

use App\Http\Requests\PanicAlert\IndexPanicAlertsRequest;
use App\Models\Area;
use App\Models\PanicAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminPanicController extends Controller
{
    public function index(IndexPanicAlertsRequest $request): Response
    {
        $user = $request->user();
        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $user->canViewAreaOperations($currentArea), 403);

        $validated = $request->validated();
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null;

        $alerts = PanicAlert::query()
            ->where('area_id', $currentArea->id)
            ->with([
                'user:id,name',
                'patrolRun:id,round_id',
                'patrolRun.round:id,title',
            ])
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PanicAlert $alert) => [
                'id' => $alert->id,
                'created_at' => $alert->created_at?->toIso8601String(),
                'guard' => $alert->user->name,
                'round' => $alert->patrolRun?->round
                    ? [
                        'id' => $alert->patrolRun->round->id,
                        'title' => $alert->patrolRun->round->title,
                    ]
                    : null,
                'patrol_id' => $alert->patrol_run_id,
            ]);

        return Inertia::render('panicos/index', [
            'area' => $currentArea->only(['id', 'name', 'code']),
            'alerts' => $alerts,
            'filters' => [
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
            ],
        ]);
    }

    private function resolveCurrentArea(Request $request): ?Area
    {
        $areaId = $request->attributes->get('current_area_id')
            ?? $request->session()->get('current_area_id');

        if (! $areaId) {
            return null;
        }

        return Area::query()->find((int) $areaId);
    }
}
