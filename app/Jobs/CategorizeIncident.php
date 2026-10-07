<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Services\Concerns\SafelyRunsSideEffects;
use App\Services\IncidentAiProcessor;
use App\Services\IncidentNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CategorizeIncident implements ShouldQueue
{
    use Queueable;
    use SafelyRunsSideEffects;

    public int $tries = 3;

    public function __construct(public Incident $incident) {}

    public function handle(IncidentAiProcessor $aiProcessor, IncidentNotifier $notifier): void
    {
        $this->safely('post-proceso de incidencia (IA/categoría/aviso)', function () use ($aiProcessor, $notifier): void {
            $incident = $this->incident->loadMissing('area');
            $result = $aiProcessor->process($incident->message_raw, $incident->area);

            $category = $this->resolveCategory($incident, $result);

            $incident->update([
                'message_cleaned' => $result['cleaned_message'],
                'incident_category_id' => $category?->id,
                'categorized_at' => $category ? now() : null,
            ]);

            if ($category) {
                $notifier->notify($incident->fresh());
            }
        });
    }

    /**
     * @param  array{
     *     cleaned_message: string,
     *     category_code: string|null,
     *     new_category: array{code: string, name: string, description: string|null}|null
     * }  $result
     */
    private function resolveCategory(Incident $incident, array $result): ?IncidentCategory
    {
        if ($result['category_code']) {
            $existing = IncidentCategory::query()
                ->where('area_id', $incident->area_id)
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
                'area_id' => $incident->area_id,
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
