<?php

namespace App\Notifications;

use App\Models\PanicAlert;
use App\Notifications\Concerns\RoutesOperationalAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PanicAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesOperationalAlert;

    public function __construct(public PanicAlert $alert)
    {
        $this->afterCommit();
    }

    /**
     * @return array{title: string, body: string, url: string, type: string, panic_alert_id: int}
     */
    public function toArray(object $notifiable): array
    {
        $this->alert->loadMissing(['user', 'area', 'patrolRun.round']);

        $body = "{$this->alert->area->name} · {$this->alert->user->name}";

        if ($this->alert->patrolRun?->round) {
            $body .= ' · '.$this->alert->patrolRun->round->title;
        }

        return [
            'title' => 'Botón de pánico',
            'body' => $body,
            'url' => $this->resolveUrl(
                $notifiable,
                route('dashboard', absolute: false),
            ),
            'type' => 'panic_alert',
            'panic_alert_id' => $this->alert->id,
        ];
    }
}
