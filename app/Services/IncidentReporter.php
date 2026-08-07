<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\PatrolRun;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class IncidentReporter
{
    public function __construct(
        private IncidentPhotoStore $photoStore,
        private IncidentAiProcessor $aiProcessor,
        private IncidentNotifier $notifier,
        private PatrolVisitRecorder $visitRecorder,
    ) {}

    /**
     * @param  list<UploadedFile>  $photos
     */
    public function report(
        User $guard,
        Area $area,
        string $message,
        bool $isUrgent,
        array $photos = [],
        ?PatrolRun $patrol = null,
        ?Checkpoint $checkpoint = null,
    ): Incident {
        $incident = DB::transaction(function () use ($guard, $area, $message, $isUrgent, $photos, $patrol, $checkpoint) {
            $incident = Incident::query()->create([
                'area_id' => $area->id,
                'user_id' => $guard->id,
                'patrol_run_id' => $patrol?->id,
                'checkpoint_id' => $checkpoint?->id,
                'message_raw' => $message,
                'is_urgent' => $isUrgent,
                'status' => IncidentStatus::Nueva,
            ]);

            $this->photoStore->store($incident, $photos);

            if ($patrol && $checkpoint) {
                $this->visitRecorder->record(
                    patrol: $patrol,
                    checkpoint: $checkpoint,
                    outcome: PatrolVisitOutcome::Incident,
                    isUrgent: $isUrgent,
                    urgentNotes: $isUrgent ? $message : null,
                );
            }

            return $incident;
        });

        $result = $this->aiProcessor->process($incident->message_raw, $area);

        $category = $this->resolveCategory($area, $result);

        $incident->update([
            'message_cleaned' => $result['cleaned_message'],
            'incident_category_id' => $category?->id,
            'categorized_at' => $category ? now() : null,
        ]);

        if ($category) {
            $this->notifier->notify($incident->fresh());
        }

        return $incident->fresh(['category', 'photos', 'checkpoint']);
    }

    /**
     * @param  array{
     *     cleaned_message: string,
     *     category_code: string|null,
     *     new_category: array{code: string, name: string, description: string|null}|null
     * }  $result
     */
    private function resolveCategory(Area $area, array $result): ?IncidentCategory
    {
        if ($result['category_code']) {
            $existing = IncidentCategory::query()
                ->where('area_id', $area->id)
                ->where('code', $result['category_code'])
                ->where('is_active', true)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $proposed = $result['new_category'] ?? null;

        if ($proposed === null) {
            return null;
        }

        $category = IncidentCategory::query()->firstOrCreate(
            [
                'area_id' => $area->id,
                'code' => $proposed['code'],
            ],
            [
                'name' => $proposed['name'],
                'description' => $proposed['description'],
                'is_active' => true,
            ],
        );

        if (! $category->is_active) {
            $category->update(['is_active' => true]);
        }

        return $category->fresh();
    }
}
