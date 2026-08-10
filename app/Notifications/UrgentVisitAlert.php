<?php

namespace App\Notifications;

use App\Models\PatrolCheckpointVisit;
use App\Notifications\Concerns\RoutesOperationalAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class UrgentVisitAlert extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesOperationalAlert;

    public function __construct(public PatrolCheckpointVisit $visit)
    {
        $this->afterCommit();
    }

    /**
     * @return array{title: string, body: string, url: string, type: string, visit_id: int, patrol_run_id: int, round_id: int}
     */
    public function toArray(object $notifiable): array
    {
        $this->visit->loadMissing([
            'checkpoint',
            'patrolRun.user',
            'patrolRun.round',
        ]);

        $round = $this->visit->patrolRun->round;
        $checkpoint = $this->visit->checkpoint;
        $guard = $this->visit->patrolRun->user;

        $body = "{$checkpoint->name} · {$guard->name}";

        if ($this->visit->urgent_notes) {
            $body .= ' — '.$this->visit->urgent_notes;
        }

        return [
            'title' => 'Punto urgente de revisión',
            'body' => $body,
            'url' => $this->resolveUrl(
                $notifiable,
                route('rondines.patrols.show', [
                    'round' => $round,
                    'patrol' => $this->visit->patrol_run_id,
                ], absolute: false),
            ),
            'type' => 'urgent_visit',
            'visit_id' => $this->visit->id,
            'patrol_run_id' => (int) $this->visit->patrol_run_id,
            'round_id' => (int) $round->id,
        ];
    }
}
