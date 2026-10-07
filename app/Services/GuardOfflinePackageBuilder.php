<?php

namespace App\Services;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Models\Area;
use App\Models\BrandingSetting;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Str;

class GuardOfflinePackageBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $guard): array
    {
        $areaIds = $guard->guardAreaIds();

        $areas = Area::query()
            ->whereIn('id', $areaIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'code' => $area->code,
                'emergency_contacts' => $this->emergencyContacts($area),
            ])
            ->values()
            ->all();

        $rounds = Round::query()
            ->whereIn('area_id', $areaIds)
            ->where('is_active', true)
            ->with([
                'checkpoints' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
                'checkpoints.questions' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
                'checkpoints.questions.options' => fn ($query) => $query->orderBy('position'),
            ])
            ->orderBy('title')
            ->get()
            ->map(fn (Round $round) => [
                'id' => $round->id,
                'area_id' => $round->area_id,
                'title' => $round->title,
                'instructions' => $round->instructions,
                'checkpoints' => $round->checkpoints->map(fn (Checkpoint $checkpoint) => [
                    'id' => $checkpoint->id,
                    'name' => $checkpoint->name,
                    'instructions' => $checkpoint->instructions,
                    'position' => $checkpoint->position,
                    'token' => $checkpoint->token,
                    'questions' => $checkpoint->questions->map(fn (CheckpointQuestion $question) => [
                        'id' => $question->id,
                        'body' => $question->body,
                        'options' => $question->options->map(fn (CheckpointQuestionOption $option) => [
                            'id' => $option->id,
                            'label' => $option->label,
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $content = [
            'areas' => $areas,
            'rounds' => $rounds,
        ];

        $branding = BrandingSetting::current();

        return [
            'version' => md5((string) json_encode($content)),
            'generated_at' => now()->toIso8601String(),
            'user' => [
                'id' => $guard->id,
                'name' => $guard->name,
                'email' => $guard->email,
            ],
            ...$content,
            'active_patrol' => $this->activePatrol($guard),
            'branding' => [
                'primary_color' => $branding->primary_color,
                'light_color' => $branding->light_color,
                'dark_color' => $branding->dark_color,
                'logo_url' => $branding->logoUrl(),
            ],
        ];
    }

    /**
     * @return list<array{name: string, phone: string}>
     */
    private function emergencyContacts(Area $area): array
    {
        return User::query()
            ->whereHas(
                'areas',
                fn ($query) => $query
                    ->where('areas.id', $area->id)
                    ->where('area_user.role', AreaRole::Contact->value),
            )
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('name')
            ->get(['name', 'phone'])
            ->map(fn (User $contact) => [
                'name' => $contact->name,
                'phone' => (string) $contact->phone,
            ])
            ->values()
            ->all();
    }

    /**
     * Patrol started online that the offline app should be able to resume.
     *
     * @return array{client_uuid: string, round_id: int, started_at: string, visited_checkpoint_ids: list<int>}|null
     */
    private function activePatrol(User $guard): ?array
    {
        $patrol = PatrolRun::query()
            ->where('user_id', $guard->id)
            ->where('status', PatrolRunStatus::InProgress)
            ->latest('started_at')
            ->first();

        if (! $patrol) {
            return null;
        }

        if ($patrol->client_uuid === null) {
            $patrol->update(['client_uuid' => (string) Str::uuid()]);
        }

        return [
            'client_uuid' => (string) $patrol->client_uuid,
            'round_id' => $patrol->round_id,
            'started_at' => $patrol->started_at->toIso8601String(),
            'visited_checkpoint_ids' => $patrol->visits()->pluck('checkpoint_id')->map(fn ($id) => (int) $id)->values()->all(),
        ];
    }
}
