<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Incident;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminIncidentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Incident::class);

        $user = $request->user();
        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $user->canManageArea($currentArea), 403);

        $incidents = Incident::query()
            ->where('area_id', $currentArea->id)
            ->with([
                'user:id,name',
                'category:id,name,code',
                'checkpoint:id,name',
                'patrolRun.round:id,title',
            ])
            ->latest()
            ->get()
            ->map(fn (Incident $incident) => $this->summarize($incident));

        return Inertia::render('incidencias/index', [
            'area' => $currentArea->only(['id', 'name', 'code']),
            'incidents' => $incidents,
        ]);
    }

    public function show(Request $request, Incident $incident): Response
    {
        $this->authorize('view', $incident);

        $incident->load([
            'area:id,name,code',
            'user:id,name,email',
            'category:id,name,code',
            'checkpoint:id,name',
            'patrolRun:id,round_id',
            'patrolRun.round:id,title',
            'photos',
        ]);

        return Inertia::render('incidencias/show', [
            'area' => $incident->area->only(['id', 'name', 'code']),
            'incident' => [
                'id' => $incident->id,
                'message_raw' => $incident->message_raw,
                'message_cleaned' => $incident->message_cleaned,
                'is_urgent' => $incident->is_urgent,
                'categorized_at' => $incident->categorized_at?->toIso8601String(),
                'created_at' => $incident->created_at?->toIso8601String(),
                'guard' => [
                    'id' => $incident->user->id,
                    'name' => $incident->user->name,
                    'email' => $incident->user->email,
                ],
                'category' => $incident->category
                    ? [
                        'id' => $incident->category->id,
                        'name' => $incident->category->name,
                        'code' => $incident->category->code,
                    ]
                    : null,
                'checkpoint' => $incident->checkpoint
                    ? [
                        'id' => $incident->checkpoint->id,
                        'name' => $incident->checkpoint->name,
                    ]
                    : null,
                'round' => $incident->patrolRun?->round
                    ? [
                        'id' => $incident->patrolRun->round->id,
                        'title' => $incident->patrolRun->round->title,
                    ]
                    : null,
                'photos' => $incident->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => $photo->url(),
                    'position' => $photo->position,
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     created_at: string|null,
     *     is_urgent: bool,
     *     message: string,
     *     guard: string,
     *     category: string|null,
     *     checkpoint: string|null,
     *     round: string|null
     * }
     */
    private function summarize(Incident $incident): array
    {
        return [
            'id' => $incident->id,
            'created_at' => $incident->created_at?->toIso8601String(),
            'is_urgent' => $incident->is_urgent,
            'message' => $incident->message_cleaned ?: $incident->message_raw,
            'guard' => $incident->user->name,
            'category' => $incident->category?->name,
            'checkpoint' => $incident->checkpoint?->name,
            'round' => $incident->patrolRun?->round?->title,
        ];
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
