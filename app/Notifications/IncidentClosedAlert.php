<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Notifications\Concerns\RoutesOperationalAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class IncidentClosedAlert extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesOperationalAlert;

    public function __construct(public Incident $incident)
    {
        $this->afterCommit();
    }

    /**
     * @return array{title: string, body: string, url: string, type: string, incident_id: int}
     */
    public function toArray(object $notifiable): array
    {
        $this->incident->loadMissing(['area', 'resolvedBy', 'category']);

        $status = $this->incident->status->label();
        $closedBy = $this->incident->resolvedBy?->name ?? '—';

        return [
            'title' => "Incidencia {$status}",
            'body' => "Folio #{$this->incident->id} · {$this->incident->area->name} · {$closedBy}",
            'url' => $this->resolveUrl(
                $notifiable,
                route('incidencias.show', $this->incident, absolute: false),
            ),
            'type' => 'incident_closed',
            'incident_id' => $this->incident->id,
        ];
    }
}
