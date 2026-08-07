<?php

namespace App\Services;

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

        $category = null;

        if ($result['category_code']) {
            $category = IncidentCategory::query()
                ->where('area_id', $area->id)
                ->where('code', $result['category_code'])
                ->where('is_active', true)
                ->first();
        }

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
}
