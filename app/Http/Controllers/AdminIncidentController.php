<?php

namespace App\Http\Controllers;

use App\Enums\IncidentStatus;
use App\Http\Requests\Incident\UpdateIncidentStatusRequest;
use App\Models\Area;
use App\Models\Incident;
use App\Services\IncidentStatusUpdater;
use App\Services\PatrolReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminIncidentController extends Controller
{
    public function __construct(
        private IncidentStatusUpdater $statusUpdater,
        private PatrolReportBuilder $reportBuilder,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Incident::class);

        $user = $request->user();
        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $user->canManageArea($currentArea), 403);

        $statusFilter = $request->string('status')->toString();
        $status = IncidentStatus::tryFrom($statusFilter);

        $incidents = Incident::query()
            ->where('area_id', $currentArea->id)
            ->with([
                'user:id,name',
                'assignedTo:id,name',
                'category:id,name,code',
                'checkpoint:id,name',
                'patrolRun.round:id,title',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(fn (Incident $incident) => $this->summarize($incident));

        return Inertia::render('incidencias/index', [
            'area' => $currentArea->only(['id', 'name', 'code']),
            'incidents' => $incidents,
            'filters' => [
                'status' => $status?->value,
            ],
            'status_options' => collect(IncidentStatus::cases())
                ->map(fn (IncidentStatus $item) => [
                    'value' => $item->value,
                    'label' => $item->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(Request $request, Incident $incident): Response
    {
        $this->authorize('view', $incident);

        $incident->load([
            'area:id,name,code',
            'user:id,name,email',
            'assignedTo:id,name',
            'resolvedBy:id,name',
            'category:id,name,code',
            'checkpoint:id,name',
            'patrolRun:id,round_id',
            'patrolRun.round:id,title',
            'photos',
        ]);

        $responseSeconds = $incident->responseSeconds();
        $resolutionSeconds = $incident->resolutionSeconds();

        return Inertia::render('incidencias/show', [
            'area' => $incident->area->only(['id', 'name', 'code']),
            'incident' => [
                'id' => $incident->id,
                'message_raw' => $incident->message_raw,
                'message_cleaned' => $incident->message_cleaned,
                'is_urgent' => $incident->is_urgent,
                'status' => $incident->status->value,
                'status_label' => $incident->status->label(),
                'allowed_transitions' => collect($incident->status->allowedTransitions())
                    ->map(fn (IncidentStatus $status) => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->values()
                    ->all(),
                'assigned_to' => $incident->assignedTo
                    ? [
                        'id' => $incident->assignedTo->id,
                        'name' => $incident->assignedTo->name,
                    ]
                    : null,
                'acknowledged_at' => $incident->acknowledged_at?->toIso8601String(),
                'resolved_by' => $incident->resolvedBy
                    ? [
                        'id' => $incident->resolvedBy->id,
                        'name' => $incident->resolvedBy->name,
                    ]
                    : null,
                'resolved_at' => $incident->resolved_at?->toIso8601String(),
                'resolution_notes' => $incident->resolution_notes,
                'response_seconds' => $responseSeconds,
                'response_label' => $this->reportBuilder->formatDuration($responseSeconds),
                'resolution_seconds' => $resolutionSeconds,
                'resolution_label' => $this->reportBuilder->formatDuration($resolutionSeconds),
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

    public function updateStatus(
        UpdateIncidentStatusRequest $request,
        Incident $incident,
    ): RedirectResponse {
        $status = IncidentStatus::from($request->validated('status'));

        $this->statusUpdater->update(
            incident: $incident,
            next: $status,
            actor: $request->user(),
            resolutionNotes: $request->validated('resolution_notes'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Estado de la incidencia actualizado.'),
        ]);

        return to_route('incidencias.show', $incident);
    }

    /**
     * @return array{
     *     id: int,
     *     created_at: string|null,
     *     is_urgent: bool,
     *     status: string,
     *     status_label: string,
     *     message: string,
     *     guard: string,
     *     assigned_to: string|null,
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
            'status' => $incident->status->value,
            'status_label' => $incident->status->label(),
            'message' => $incident->message_cleaned ?: $incident->message_raw,
            'guard' => $incident->user->name,
            'assigned_to' => $incident->assignedTo?->name,
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
