<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Area;
use App\Models\Incident;
use App\Models\PatrolCheckpointVisit;
use App\Models\Round;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OperationalReports
{
    public function __construct(private PatrolReportBuilder $reportBuilder) {}

    /**
     * @return array{
     *     totals: array{
     *         total: int,
     *         open: int,
     *         resolved: int,
     *         discarded: int,
     *         urgent: int
     *     },
     *     by_category: list<array{category: string, total: int, urgent: int, open: int, resolved: int, discarded: int}>,
     *     series: list<array{date: string, label: string, count: int}>
     * }
     */
    public function volumen(Area $area, CarbonInterface $from, CarbonInterface $to): array
    {
        $incidents = Incident::query()
            ->where('area_id', $area->id)
            ->whereBetween('created_at', [$from, $to])
            ->with('category:id,name')
            ->get(['id', 'incident_category_id', 'status', 'is_urgent', 'created_at']);

        $totals = [
            'total' => $incidents->count(),
            'open' => $incidents->filter(fn (Incident $incident) => $incident->status->isOpen())->count(),
            'resolved' => $incidents->where('status', IncidentStatus::Resuelta)->count(),
            'discarded' => $incidents->where('status', IncidentStatus::Descartada)->count(),
            'urgent' => $incidents->where('is_urgent', true)->count(),
        ];

        $byCategory = $incidents
            ->groupBy(fn (Incident $incident) => $incident->category?->name ?? 'Sin categoría')
            ->map(fn (Collection $group, string $category) => [
                'category' => $category,
                'total' => $group->count(),
                'urgent' => $group->where('is_urgent', true)->count(),
                'open' => $group->filter(fn (Incident $incident) => $incident->status->isOpen())->count(),
                'resolved' => $group->where('status', IncidentStatus::Resuelta)->count(),
                'discarded' => $group->where('status', IncidentStatus::Descartada)->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'totals' => $totals,
            'by_category' => $byCategory,
            'series' => $this->dailySeries($incidents, $from, $to),
        ];
    }

    /**
     * @return array{
     *     summary: array{
     *         with_response: int,
     *         without_response: int,
     *         with_resolution: int,
     *         without_resolution: int,
     *         avg_response_seconds: int|null,
     *         avg_response_label: string|null,
     *         median_response_seconds: int|null,
     *         median_response_label: string|null,
     *         avg_resolution_seconds: int|null,
     *         avg_resolution_label: string|null,
     *         median_resolution_seconds: int|null,
     *         median_resolution_label: string|null,
     *         urgent_avg_response_seconds: int|null,
     *         urgent_avg_response_label: string|null,
     *         non_urgent_avg_response_seconds: int|null,
     *         non_urgent_avg_response_label: string|null
     *     },
     *     by_category: list<array{
     *         category: string,
     *         with_response: int,
     *         avg_response_seconds: int|null,
     *         avg_response_label: string|null,
     *         with_resolution: int,
     *         avg_resolution_seconds: int|null,
     *         avg_resolution_label: string|null
     *     }>
     * }
     */
    public function tiempos(Area $area, CarbonInterface $from, CarbonInterface $to): array
    {
        $incidents = Incident::query()
            ->where('area_id', $area->id)
            ->whereBetween('created_at', [$from, $to])
            ->with('category:id,name')
            ->get([
                'id',
                'incident_category_id',
                'is_urgent',
                'created_at',
                'acknowledged_at',
                'resolved_at',
            ]);

        $responseSeconds = $incidents
            ->filter(fn (Incident $incident) => $incident->acknowledged_at !== null)
            ->map(fn (Incident $incident) => $incident->responseSeconds())
            ->filter(fn (?int $seconds) => $seconds !== null)
            ->values();

        $resolutionSeconds = $incidents
            ->filter(fn (Incident $incident) => $incident->resolved_at !== null)
            ->map(fn (Incident $incident) => $incident->resolutionSeconds())
            ->filter(fn (?int $seconds) => $seconds !== null)
            ->values();

        $urgentResponse = $incidents
            ->where('is_urgent', true)
            ->filter(fn (Incident $incident) => $incident->acknowledged_at !== null)
            ->map(fn (Incident $incident) => $incident->responseSeconds())
            ->filter(fn (?int $seconds) => $seconds !== null)
            ->values();

        $nonUrgentResponse = $incidents
            ->where('is_urgent', false)
            ->filter(fn (Incident $incident) => $incident->acknowledged_at !== null)
            ->map(fn (Incident $incident) => $incident->responseSeconds())
            ->filter(fn (?int $seconds) => $seconds !== null)
            ->values();

        $avgResponse = $this->average($responseSeconds);
        $medianResponse = $this->median($responseSeconds);
        $avgResolution = $this->average($resolutionSeconds);
        $medianResolution = $this->median($resolutionSeconds);
        $urgentAvgResponse = $this->average($urgentResponse);
        $nonUrgentAvgResponse = $this->average($nonUrgentResponse);

        $byCategory = $incidents
            ->groupBy(fn (Incident $incident) => $incident->category?->name ?? 'Sin categoría')
            ->map(function (Collection $group, string $category) {
                $responses = $group
                    ->filter(fn (Incident $incident) => $incident->acknowledged_at !== null)
                    ->map(fn (Incident $incident) => $incident->responseSeconds())
                    ->filter(fn (?int $seconds) => $seconds !== null)
                    ->values();

                $resolutions = $group
                    ->filter(fn (Incident $incident) => $incident->resolved_at !== null)
                    ->map(fn (Incident $incident) => $incident->resolutionSeconds())
                    ->filter(fn (?int $seconds) => $seconds !== null)
                    ->values();

                $avgResponse = $this->average($responses);
                $avgResolution = $this->average($resolutions);

                return [
                    'category' => $category,
                    'with_response' => $responses->count(),
                    'avg_response_seconds' => $avgResponse,
                    'avg_response_label' => $this->reportBuilder->formatDuration($avgResponse),
                    'with_resolution' => $resolutions->count(),
                    'avg_resolution_seconds' => $avgResolution,
                    'avg_resolution_label' => $this->reportBuilder->formatDuration($avgResolution),
                ];
            })
            ->sortBy('category')
            ->values()
            ->all();

        return [
            'summary' => [
                'with_response' => $responseSeconds->count(),
                'without_response' => $incidents->count() - $responseSeconds->count(),
                'with_resolution' => $resolutionSeconds->count(),
                'without_resolution' => $incidents->count() - $resolutionSeconds->count(),
                'avg_response_seconds' => $avgResponse,
                'avg_response_label' => $this->reportBuilder->formatDuration($avgResponse),
                'median_response_seconds' => $medianResponse,
                'median_response_label' => $this->reportBuilder->formatDuration($medianResponse),
                'avg_resolution_seconds' => $avgResolution,
                'avg_resolution_label' => $this->reportBuilder->formatDuration($avgResolution),
                'median_resolution_seconds' => $medianResolution,
                'median_resolution_label' => $this->reportBuilder->formatDuration($medianResolution),
                'urgent_avg_response_seconds' => $urgentAvgResponse,
                'urgent_avg_response_label' => $this->reportBuilder->formatDuration($urgentAvgResponse),
                'non_urgent_avg_response_seconds' => $nonUrgentAvgResponse,
                'non_urgent_avg_response_label' => $this->reportBuilder->formatDuration($nonUrgentAvgResponse),
            ],
            'by_category' => $byCategory,
        ];
    }

    /**
     * @return list<array{
     *     checkpoint_id: int|null,
     *     checkpoint: string,
     *     round: string|null,
     *     incidents: int,
     *     urgent_visits: int,
     *     score: int,
     *     last_at: string|null
     * }>
     */
    public function puntosCriticos(Area $area, CarbonInterface $from, CarbonInterface $to): array
    {
        $roundIds = Round::query()
            ->where('area_id', $area->id)
            ->pluck('id');

        $incidents = Incident::query()
            ->where('area_id', $area->id)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('checkpoint_id')
            ->with([
                'checkpoint:id,name,round_id',
                'checkpoint.round:id,title',
            ])
            ->get(['id', 'checkpoint_id', 'created_at']);

        $urgentVisits = PatrolCheckpointVisit::query()
            ->where('is_urgent', true)
            ->whereBetween('reviewed_at', [$from, $to])
            ->whereHas('patrolRun', fn ($query) => $query->whereIn('round_id', $roundIds))
            ->with([
                'checkpoint:id,name,round_id',
                'checkpoint.round:id,title',
            ])
            ->get(['id', 'checkpoint_id', 'reviewed_at']);

        $rows = [];

        foreach ($incidents->groupBy('checkpoint_id') as $checkpointId => $group) {
            /** @var Incident $sample */
            $sample = $group->first();
            $key = (string) $checkpointId;
            $rows[$key] = [
                'checkpoint_id' => (int) $checkpointId,
                'checkpoint' => $sample->checkpoint?->name ?? 'Punto eliminado',
                'round' => $sample->checkpoint?->round?->title,
                'incidents' => $group->count(),
                'urgent_visits' => 0,
                'score' => $group->count(),
                'last_at' => $group->max('created_at')?->toIso8601String(),
            ];
        }

        foreach ($urgentVisits->groupBy('checkpoint_id') as $checkpointId => $group) {
            /** @var PatrolCheckpointVisit $sample */
            $sample = $group->first();
            $key = (string) $checkpointId;
            $lastVisit = $group->max('reviewed_at')?->toIso8601String();

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'checkpoint_id' => (int) $checkpointId,
                    'checkpoint' => $sample->checkpoint?->name ?? 'Punto eliminado',
                    'round' => $sample->checkpoint?->round?->title,
                    'incidents' => 0,
                    'urgent_visits' => $group->count(),
                    'score' => $group->count(),
                    'last_at' => $lastVisit,
                ];

                continue;
            }

            $rows[$key]['urgent_visits'] = $group->count();
            $rows[$key]['score'] = $rows[$key]['incidents'] + $group->count();

            if ($lastVisit && ($rows[$key]['last_at'] === null || $lastVisit > $rows[$key]['last_at'])) {
                $rows[$key]['last_at'] = $lastVisit;
            }
        }

        return collect($rows)
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Incident>  $incidents
     * @return list<array{date: string, label: string, count: int}>
     */
    private function dailySeries(Collection $incidents, CarbonInterface $from, CarbonInterface $to): array
    {
        $counts = $incidents
            ->groupBy(fn (Incident $incident) => $incident->created_at->toDateString())
            ->map->count();

        $series = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $series[] = [
                'date' => $date,
                'label' => $cursor->locale('es')->isoFormat('D MMM'),
                'count' => (int) ($counts[$date] ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }

    /**
     * @param  Collection<int, int>  $values
     */
    private function average(Collection $values): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        return (int) round($values->avg());
    }

    /**
     * @param  Collection<int, int>  $values
     */
    private function median(Collection $values): ?int
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (int) $sorted[$middle];
        }

        return (int) round(($sorted[$middle - 1] + $sorted[$middle]) / 2);
    }
}
