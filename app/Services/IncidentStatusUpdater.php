<?php

namespace App\Services;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class IncidentStatusUpdater
{
    public function __construct(private IncidentClosureNotifier $closureNotifier) {}

    public function update(
        Incident $incident,
        IncidentStatus $next,
        User $actor,
        ?string $resolutionNotes = null,
    ): Incident {
        $current = $incident->status;

        if (! $current->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => __('No se puede pasar de :from a :to.', [
                    'from' => $current->label(),
                    'to' => $next->label(),
                ]),
            ]);
        }

        if ($next->isTerminal() && blank($resolutionNotes)) {
            throw ValidationException::withMessages([
                'resolution_notes' => __('Las notas de cierre son obligatorias.'),
            ]);
        }

        $attributes = [
            'status' => $next,
        ];

        if ($next === IncidentStatus::EnAtencion) {
            $attributes['assigned_to_id'] = $actor->id;

            if ($incident->acknowledged_at === null) {
                $attributes['acknowledged_at'] = now();
            }
        }

        if ($next->isTerminal()) {
            if ($incident->assigned_to_id === null) {
                $attributes['assigned_to_id'] = $actor->id;
            }

            if ($incident->acknowledged_at === null) {
                $attributes['acknowledged_at'] = now();
            }

            $attributes['resolved_by_id'] = $actor->id;
            $attributes['resolved_at'] = now();
            $attributes['resolution_notes'] = trim((string) $resolutionNotes);
        }

        $incident->update($attributes);

        $incident = $incident->fresh([
            'user',
            'assignedTo',
            'resolvedBy',
            'category.contacts',
            'area',
            'checkpoint',
            'patrolRun.round',
        ]);

        if ($next->isTerminal() && $incident) {
            $this->closureNotifier->notify($incident);
        }

        return $incident;
    }
}
