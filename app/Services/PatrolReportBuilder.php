<?php

namespace App\Services;

use App\Enums\PatrolRunStatus;
use App\Models\Area;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

class PatrolReportBuilder
{
    /**
     * @return array{
     *     area: array{id: int, name: string, code: string},
     *     round: array{id: int, title: string},
     *     patrol: array{
     *         id: int,
     *         status: string,
     *         status_label: string,
     *         started_at: string,
     *         finished_at: string|null,
     *         duration_seconds: int|null,
     *         duration_label: string|null,
     *         guard: array{id: int, name: string, email: string}
     *     },
     *     checkpoints: list<array{
     *         id: int,
     *         name: string,
     *         position: int,
     *         visited: bool,
     *         reviewed_at: string|null,
     *         outcome: string|null,
     *         outcome_label: string|null,
     *         answers: list<array{question: string, option: string}>,
     *         photos: list<array{id: int, url: string, path: string, position: int}>
     *     }>
     * }
     */
    public function build(PatrolRun $patrol): array
    {
        $patrol->loadMissing([
            'user:id,name,email',
            'round.area:id,name,code',
            'round.checkpoints' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
            'visits.photos',
            'visits.submission.answers.question',
            'visits.submission.answers.option',
        ]);

        $visitsByCheckpoint = $patrol->visits->keyBy('checkpoint_id');

        $checkpoints = $patrol->round->checkpoints->map(function ($checkpoint) use ($visitsByCheckpoint) {
            $visit = $visitsByCheckpoint->get($checkpoint->id);

            $answers = [];
            $photos = [];

            if ($visit) {
                $answers = $visit->submission
                    ? $visit->submission->answers->map(fn ($answer) => [
                        'question' => $answer->question?->body ?? '',
                        'option' => $answer->option?->label ?? '',
                    ])->values()->all()
                    : [];

                $photos = $visit->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => Storage::disk('public')->url($photo->path),
                    'path' => $photo->path,
                    'position' => $photo->position,
                ])->values()->all();
            }

            return [
                'id' => $checkpoint->id,
                'visit_id' => $visit?->id,
                'name' => $checkpoint->name,
                'position' => $checkpoint->position,
                'visited' => $visit !== null,
                'reviewed_at' => $visit?->reviewed_at?->toIso8601String(),
                'outcome' => $visit?->outcome?->value,
                'outcome_label' => $visit?->outcome?->label(),
                'is_urgent' => (bool) ($visit?->is_urgent ?? false),
                'urgent_notes' => $visit?->urgent_notes,
                'urgent_resolved_at' => $visit?->urgent_resolved_at?->toIso8601String(),
                'answers' => $answers,
                'photos' => $photos,
            ];
        })->values()->all();

        $durationSeconds = $patrol->durationInSeconds();

        return [
            'area' => $patrol->round->area->only(['id', 'name', 'code']),
            'round' => $patrol->round->only(['id', 'title']),
            'patrol' => [
                'id' => $patrol->id,
                'status' => $patrol->status->value,
                'status_label' => $patrol->status->label(),
                'started_at' => $patrol->started_at->toIso8601String(),
                'finished_at' => $patrol->finished_at?->toIso8601String(),
                'duration_seconds' => $durationSeconds,
                'duration_label' => $this->formatDuration($durationSeconds),
                'guard' => $patrol->user->only(['id', 'name', 'email']),
            ],
            'checkpoints' => $checkpoints,
        ];
    }

    /**
     * @return list<array{
     *     id: int,
     *     status: string,
     *     status_label: string,
     *     started_at: string,
     *     finished_at: string|null,
     *     duration_seconds: int|null,
     *     duration_label: string|null,
     *     guard: array{id: int, name: string}
     * }>
     */
    public function summarizeRuns(Round $round): array
    {
        $runs = $round->patrolRuns()
            ->with('user:id,name')
            ->latest('started_at')
            ->get();

        return $runs
            ->map(fn (PatrolRun $patrol) => $this->summarizePatrol($patrol))
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     id: int,
     *     status: string,
     *     status_label: string,
     *     started_at: string,
     *     finished_at: string|null,
     *     duration_seconds: int|null,
     *     duration_label: string|null,
     *     guard: array{id: int, name: string},
     *     round: array{id: int, title: string}
     * }>
     */
    public function summarizeAreaRuns(
        Area $area,
        PatrolRunStatus $status = PatrolRunStatus::Completed,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): array {
        $roundIds = $area->rounds()->pluck('id');

        $query = PatrolRun::query()
            ->whereIn('round_id', $roundIds)
            ->where('status', $status)
            ->with(['user:id,name', 'round:id,title']);

        if ($status === PatrolRunStatus::Completed) {
            $query->when($from, fn ($builder) => $builder->where('finished_at', '>=', $from))
                ->when($to, fn ($builder) => $builder->where('finished_at', '<=', $to))
                ->latest('finished_at');
        } else {
            $query->when($from, fn ($builder) => $builder->where('started_at', '>=', $from))
                ->when($to, fn ($builder) => $builder->where('started_at', '<=', $to))
                ->latest('started_at');
        }

        return $query
            ->get()
            ->map(fn (PatrolRun $patrol) => $this->summarizePatrol($patrol, withRound: true))
            ->values()
            ->all();
    }

    /**
     * @return list<array{
     *     id: int,
     *     reviewed_at: string|null,
     *     urgent_notes: string|null,
     *     checkpoint: string,
     *     round: array{id: int, title: string},
     *     guard: string,
     *     patrol_id: int
     * }>
     */
    public function summarizeUrgentVisits(Area $area, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $roundIds = $area->rounds()->pluck('id');

        return PatrolCheckpointVisit::query()
            ->where('is_urgent', true)
            ->whereHas('patrolRun', fn ($query) => $query->whereIn('round_id', $roundIds))
            ->when($from, fn ($query) => $query->where('reviewed_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('reviewed_at', '<=', $to))
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
                'reviewed_at' => $visit->reviewed_at?->toIso8601String(),
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
     * @return array{
     *     id: int,
     *     status: string,
     *     status_label: string,
     *     started_at: string,
     *     finished_at: string|null,
     *     duration_seconds: int|null,
     *     duration_label: string|null,
     *     guard: array{id: int, name: string},
     *     round?: array{id: int, title: string}
     * }
     */
    private function summarizePatrol(PatrolRun $patrol, bool $withRound = false): array
    {
        $durationSeconds = $patrol->durationInSeconds()
            ?? ($patrol->status === PatrolRunStatus::InProgress
                ? (int) $patrol->started_at->diffInSeconds(now())
                : null);

        $summary = [
            'id' => $patrol->id,
            'status' => $patrol->status->value,
            'status_label' => $patrol->status->label(),
            'started_at' => $patrol->started_at->toIso8601String(),
            'finished_at' => $patrol->finished_at?->toIso8601String(),
            'duration_seconds' => $durationSeconds,
            'duration_label' => $this->formatDuration($durationSeconds),
            'guard' => [
                'id' => $patrol->user->id,
                'name' => $patrol->user->name,
            ],
        ];

        if ($withRound) {
            $summary['round'] = [
                'id' => $patrol->round->id,
                'title' => $patrol->round->title,
            ];
        }

        return $summary;
    }

    public function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $remainingSeconds);
        }

        if ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $remainingSeconds);
        }

        return sprintf('%ds', $remainingSeconds);
    }
}
