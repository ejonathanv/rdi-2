<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Area;
use App\Models\Incident;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DigestReports
{
    public const TIMEZONE = 'America/Mexico_City';

    public function __construct(private PatrolReportBuilder $reportBuilder) {}

    /**
     * @return Collection<int, User>
     */
    public function contactsForArea(Area $area): Collection
    {
        return $area->contacts()->get()->unique('id')->values();
    }

    /**
     * @return array{
     *     area: array{id: int, name: string, code: string},
     *     incidents: list<array{id: int, created_at: string, message: string, status: string, category: string|null, guard: string}>,
     *     visits: list<array{id: int, reviewed_at: string, checkpoint: string, round: string, guard: string, notes: string|null, patrol_id: int, round_id: int}>
     * }
     */
    public function openUrgents(Area $area): array
    {
        $roundIds = Round::query()->where('area_id', $area->id)->pluck('id');

        $incidents = Incident::query()
            ->where('area_id', $area->id)
            ->where('is_urgent', true)
            ->whereIn('status', [
                IncidentStatus::Nueva,
                IncidentStatus::EnAtencion,
            ])
            ->with(['category:id,name', 'user:id,name'])
            ->latest()
            ->get()
            ->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'created_at' => $incident->created_at?->timezone(self::TIMEZONE)->format('d/m/Y H:i') ?? '',
                'message' => $incident->message_cleaned ?: $incident->message_raw,
                'status' => $incident->status->label(),
                'category' => $incident->category?->name,
                'guard' => $incident->user->name,
            ])
            ->values()
            ->all();

        $visits = PatrolCheckpointVisit::query()
            ->where('is_urgent', true)
            ->whereNull('urgent_resolved_at')
            ->whereHas('patrolRun', fn ($query) => $query->whereIn('round_id', $roundIds))
            ->with([
                'checkpoint:id,name',
                'patrolRun:id,user_id,round_id',
                'patrolRun.user:id,name',
                'patrolRun.round:id,title',
            ])
            ->latest('reviewed_at')
            ->get()
            ->map(fn (PatrolCheckpointVisit $visit) => [
                'id' => $visit->id,
                'reviewed_at' => $visit->reviewed_at->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                'checkpoint' => $visit->checkpoint->name,
                'round' => $visit->patrolRun->round->title,
                'guard' => $visit->patrolRun->user->name,
                'notes' => $visit->urgent_notes,
                'patrol_id' => $visit->patrol_run_id,
                'round_id' => $visit->patrolRun->round_id,
            ])
            ->values()
            ->all();

        return [
            'area' => $area->only(['id', 'name', 'code']),
            'incidents' => $incidents,
            'visits' => $visits,
        ];
    }

    public function hasOpenUrgents(array $payload): bool
    {
        return count($payload['incidents']) > 0 || count($payload['visits']) > 0;
    }

    /**
     * @return array{
     *     area: array{id: int, name: string, code: string},
     *     period: array{from: string, to: string},
     *     totals: array{total: int, open: int, resolved: int, discarded: int, urgent: int},
     *     incidents: list<array{id: int, created_at: string, message: string, status: string, category: string|null, is_urgent: bool, guard: string}>
     * }
     */
    public function weeklyIncidents(Area $area, ?CarbonInterface $now = null): array
    {
        $local = ($now ?? now())->copy()->timezone(self::TIMEZONE);
        $fromLocal = $local->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $toLocal = $local->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $incidents = Incident::query()
            ->where('area_id', $area->id)
            ->whereBetween('created_at', [$fromLocal->clone()->utc(), $toLocal->clone()->utc()])
            ->with(['category:id,name', 'user:id,name'])
            ->latest()
            ->get();

        return [
            'area' => $area->only(['id', 'name', 'code']),
            'period' => [
                'from' => $fromLocal->format('d/m/Y'),
                'to' => $toLocal->format('d/m/Y'),
            ],
            'totals' => [
                'total' => $incidents->count(),
                'open' => $incidents->filter(fn (Incident $incident) => $incident->status->isOpen())->count(),
                'resolved' => $incidents->where('status', IncidentStatus::Resuelta)->count(),
                'discarded' => $incidents->where('status', IncidentStatus::Descartada)->count(),
                'urgent' => $incidents->where('is_urgent', true)->count(),
            ],
            'incidents' => $incidents->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'created_at' => $incident->created_at?->timezone(self::TIMEZONE)->format('d/m/Y H:i') ?? '',
                'message' => $incident->message_cleaned ?: $incident->message_raw,
                'status' => $incident->status->label(),
                'category' => $incident->category?->name,
                'is_urgent' => $incident->is_urgent,
                'guard' => $incident->user->name,
            ])->values()->all(),
        ];
    }

    /**
     * @return array{
     *     area: array{id: int, name: string, code: string},
     *     date: string,
     *     patrols: list<array{
     *         id: int,
     *         round: string,
     *         guard: string,
     *         status: string,
     *         started_at: string,
     *         finished_at: string|null,
     *         duration_label: string|null,
     *         visits_count: int
     *     }>
     * }
     */
    public function dailyPatrols(Area $area, ?CarbonInterface $now = null): array
    {
        $local = ($now ?? now())->copy()->timezone(self::TIMEZONE);
        $from = $local->copy()->startOfDay()->utc();
        $to = $local->copy()->endOfDay()->utc();

        $roundIds = Round::query()->where('area_id', $area->id)->pluck('id');

        $patrols = PatrolRun::query()
            ->whereIn('round_id', $roundIds)
            ->whereBetween('started_at', [$from, $to])
            ->with(['user:id,name', 'round:id,title'])
            ->withCount('visits')
            ->latest('started_at')
            ->get()
            ->map(function (PatrolRun $patrol) {
                $duration = $patrol->durationInSeconds();

                return [
                    'id' => $patrol->id,
                    'round' => $patrol->round->title,
                    'guard' => $patrol->user->name,
                    'status' => $patrol->status->label(),
                    'started_at' => $patrol->started_at->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                    'finished_at' => $patrol->finished_at?->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                    'duration_label' => $this->reportBuilder->formatDuration($duration),
                    'visits_count' => $patrol->visits_count,
                ];
            })
            ->values()
            ->all();

        return [
            'area' => $area->only(['id', 'name', 'code']),
            'date' => $local->format('d/m/Y'),
            'patrols' => $patrols,
        ];
    }
}
