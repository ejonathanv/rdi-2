<?php

namespace App\Notifications;

use App\Models\Incident;
use App\Notifications\Concerns\RoutesOperationalAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class IncidentCreatedAlert extends Notification implements ShouldQueue
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
        $this->incident->loadMissing(['category', 'user', 'area']);

        $title = $this->incident->is_urgent
            ? 'Incidencia urgente'
            : 'Incidencia registrada';

        $category = $this->incident->category?->name ?? 'Sin categoría';
        $body = "{$this->incident->area->name} · {$category} · {$this->incident->user->name}";

        return [
            'title' => $title,
            'body' => $body,
            'url' => $this->resolveUrl(
                $notifiable,
                route('incidencias.show', $this->incident, absolute: false),
            ),
            'type' => 'incident_created',
            'incident_id' => $this->incident->id,
        ];
    }
}
