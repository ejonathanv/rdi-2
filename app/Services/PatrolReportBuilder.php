<?php

namespace App\Services;

use App\Models\PatrolRun;
use App\Models\Round;
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
                'name' => $checkpoint->name,
                'position' => $checkpoint->position,
                'visited' => $visit !== null,
                'reviewed_at' => $visit?->reviewed_at?->toIso8601String(),
                'outcome' => $visit?->outcome?->value,
                'outcome_label' => $visit?->outcome?->label(),
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

        return $runs->map(function (PatrolRun $patrol) {
            $durationSeconds = $patrol->durationInSeconds();

            return [
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
        })->values()->all();
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
