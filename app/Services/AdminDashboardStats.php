<?php

namespace App\Services;

use App\Enums\PatrolRunStatus;
use App\Models\Area;
use App\Models\Incident;
use App\Models\PanicAlert;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AdminDashboardStats
{
    public function __construct(private PatrolReportBuilder $reportBuilder) {}

    /**
     * @return array{
     *     area: array{id: int, name: string, code: string}|null,
     *     kpis: array{
     *         urgents_today: int,
     *         in_progress: int,
     *         completed_today: int,
     *         average_duration_seconds: int|null,
     *         average_duration_label: string|null,
     *         incidents_today: int,
     *         urgent_incidents_today: int,
     *         panics_today: int
     *     },
     *     recent_urgents: list<array{
     *         id: int,
     *         reviewed_at: string,
     *         urgent_notes: string|null,
     *         checkpoint: string,
     *         round: array{id: int, title: string},
     *         guard: string,
     *         patrol_id: int
     *     }>,
     *     recent_incidents: list<array{
     *         id: int,
     *         created_at: string,
     *         is_urgent: bool,
     *         message: string,
     *         category: string|null,
     *         guard: string
     *     }>,
     *     active_patrols: list<array{
     *         id: int,
     *         started_at: string,
     *         duration_so_far_label: string,
     *         round: array{id: int, title: string},
     *         guard: string
     *     }>,
     *     completed_last_7_days: list<array{date: string, label: string, count: int}>,
     *     incidents_last_7_days: list<array{date: string, label: string, count: int}>
     * }
     */
    public function forArea(?Area $area): array
    {
        if (! $area) {
            return [
                'area' => null,
                'kpis' => [
                    'urgents_today' => 0,
                    'in_progress' => 0,
                    'completed_today' => 0,
                    'average_duration_seconds' => null,
                    'average_duration_label' => null,
                    'incidents_today' => 0,
                    'urgent_incidents_today' => 0,
                    'panics_today' => 0,
                ],
                'recent_urgents' => [],
                'recent_incidents' => [],
                'active_patrols' => [],
                'completed_last_7_days' => $this->emptyLast7Days(),
                'incidents_last_7_days' => $this->emptyLast7Days(),
            ];
        }

        $roundIds = Round::query()
            ->where('area_id', $area->id)
            ->pluck('id');

        $todayStart = now()->startOfDay();
        $weekStart = now()->subDays(6)->startOfDay();

        $urgentsToday = PatrolCheckpointVisit::query()
            ->where('is_urgent', true)
            ->where('reviewed_at', '>=', $todayStart)
            ->whereHas('patrolRun', fn ($query) => $query->whereIn('round_id', $roundIds))
            ->count();

        $inProgress = PatrolRun::query()
            ->whereIn('round_id', $roundIds)
            ->where('status', PatrolRunStatus::InProgress)
            ->count();

        $completedToday = PatrolRun::query()
            ->whereIn('round_id', $roundIds)
            ->where('status', PatrolRunStatus::Completed)
            ->where('finished_at', '>=', $todayStart)
            ->count();

        $avgSeconds = $this->averageCompletedDurationSeconds($roundIds, $weekStart);

        $incidentsToday = Incident::query()
            ->where('area_id', $area->id)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $urgentIncidentsToday = Incident::query()
            ->where('area_id', $area->id)
            ->where('is_urgent', true)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $panicsToday = PanicAlert::query()
            ->where('area_id', $area->id)
            ->where('created_at', '>=', $todayStart)
            ->count();

        return [
            'area' => $area->only(['id', 'name', 'code']),
            'kpis' => [
                'urgents_today' => $urgentsToday,
                'in_progress' => $inProgress,
                'completed_today' => $completedToday,
                'average_duration_seconds' => $avgSeconds,
                'average_duration_label' => $this->reportBuilder->formatDuration($avgSeconds),
                'incidents_today' => $incidentsToday,
                'urgent_incidents_today' => $urgentIncidentsToday,
                'panics_today' => $panicsToday,
            ],
            'recent_urgents' => $this->recentUrgents($roundIds),
            'recent_incidents' => $this->recentIncidents($area->id),
            'active_patrols' => $this->activePatrols($roundIds),
            'completed_last_7_days' => $this->completedLast7Days($roundIds),
            'incidents_last_7_days' => $this->incidentsLast7Days($area->id),
        ];
    }

    /**
     * @param  Collection<int, int>  $roundIds
     */
    private function averageCompletedDurationSeconds(Collection $roundIds, CarbonInterface $since): ?int
    {
        $runs = PatrolRun::query()
            ->whereIn('round_id', $roundIds)
            ->where('status', PatrolRunStatus::Completed)
            ->whereNotNull('finished_at')
            ->where('finished_at', '>=', $since)
            ->get(['started_at', 'finished_at']);

        if ($runs->isEmpty()) {
            return null;
        }

        $total = $runs->sum(
            fn (PatrolRun $run) => (int) $run->started_at->diffInSeconds($run->finished_at),
        );

        return (int) round($total / $runs->count());
    }

    /**
     * @param  Collection<int, int>  $roundIds
     * @return list<array{id: int, reviewed_at: string, urgent_notes: string|null, checkpoint: string, round: array{id: int, title: string}, guard: string, patrol_id: int}>
     */
    private function recentUrgents(Collection $roundIds): array
    {
        return PatrolCheckpointVisit::query()
            ->where('is_urgent', true)
            ->whereHas('patrolRun', fn ($query) => $query->whereIn('round_id', $roundIds))
            ->with([
                'checkpoint:id,name',
                'patrolRun:id,user_id,round_id',
                'patrolRun.user:id,name',
                'patrolRun.round:id,title',
            ])
            ->latest('reviewed_at')
            ->limit(5)
            ->get()
            ->map(fn (PatrolCheckpointVisit $visit) => [
                'id' => $visit->id,
                'reviewed_at' => $visit->reviewed_at->toIso8601String(),
                'urgent_notes' => $visit->urgent_notes,
                'checkpoint' => $visit->checkpoint->name,
                'round' => [
                    'id' => $visit->patrolRun->round->id,
                    'title' => $visit->patrolRun->round->title,
                ],
                'guard' => $visit->patrolRun->user->name,
                'patrol_id' => $visit->patrolRun->id,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, created_at: string, is_urgent: bool, message: string, category: string|null, guard: string}>
     */
    private function recentIncidents(int $areaId): array
    {
        return Incident::query()
            ->where('area_id', $areaId)
            ->with(['user:id,name', 'category:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Incident $incident) => [
                'id' => $incident->id,
                'created_at' => $incident->created_at->toIso8601String(),
                'is_urgent' => $incident->is_urgent,
                'message' => $incident->message_cleaned ?: $incident->message_raw,
                'category' => $incident->category?->name,
                'guard' => $incident->user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int>  $roundIds
     * @return list<array{id: int, started_at: string, duration_so_far_label: string, round: array{id: int, title: string}, guard: string}>
     */
    private function activePatrols(Collection $roundIds): array
    {
        return PatrolRun::query()
            ->whereIn('round_id', $roundIds)
            ->where('status', PatrolRunStatus::InProgress)
            ->with(['user:id,name', 'round:id,title'])
            ->latest('started_at')
            ->limit(10)
            ->get()
            ->map(function (PatrolRun $patrol) {
                $seconds = (int) $patrol->started_at->diffInSeconds(now());

                return [
                    'id' => $patrol->id,
                    'started_at' => $patrol->started_at->toIso8601String(),
                    'duration_so_far_label' => $this->reportBuilder->formatDuration($seconds) ?? '0s',
                    'round' => [
                        'id' => $patrol->round->id,
                        'title' => $patrol->round->title,
                    ],
                    'guard' => $patrol->user->name,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int>  $roundIds
     * @return list<array{date: string, label: string, count: int}>
     */
    private function completedLast7Days(Collection $roundIds): array
    {
        $days = collect(range(6, 0))->map(function (int $offset) {
            $day = now()->subDays($offset)->startOfDay();

            return [
                'date' => $day->toDateString(),
                'label' => $day->locale('es')->isoFormat('dd D'),
                'count' => 0,
            ];
        })->keyBy('date');

        $counts = PatrolRun::query()
            ->whereIn('round_id', $roundIds)
            ->where('status', PatrolRunStatus::Completed)
            ->where('finished_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['finished_at'])
            ->groupBy(fn (PatrolRun $run) => $run->finished_at?->toDateString())
            ->map->count();

        foreach ($counts as $date => $count) {
            if ($days->has($date)) {
                $day = $days->get($date);
                $day['count'] = $count;
                $days->put($date, $day);
            }
        }

        return $days->values()->all();
    }

    /**
     * @return list<array{date: string, label: string, count: int}>
     */
    private function incidentsLast7Days(int $areaId): array
    {
        $days = collect(range(6, 0))->map(function (int $offset) {
            $day = now()->subDays($offset)->startOfDay();

            return [
                'date' => $day->toDateString(),
                'label' => $day->locale('es')->isoFormat('dd D'),
                'count' => 0,
            ];
        })->keyBy('date');

        $counts = Incident::query()
            ->where('area_id', $areaId)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn (Incident $incident) => $incident->created_at?->toDateString())
            ->map->count();

        foreach ($counts as $date => $count) {
            if ($days->has($date)) {
                $day = $days->get($date);
                $day['count'] = $count;
                $days->put($date, $day);
            }
        }

        return $days->values()->all();
    }

    /**
     * @return list<array{date: string, label: string, count: int}>
     */
    private function emptyLast7Days(): array
    {
        return collect(range(6, 0))->map(function (int $offset) {
            $day = now()->subDays($offset)->startOfDay();

            return [
                'date' => $day->toDateString(),
                'label' => $day->locale('es')->isoFormat('dd D'),
                'count' => 0,
            ];
        })->values()->all();
    }
}
