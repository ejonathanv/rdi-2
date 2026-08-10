<?php

namespace App\Services;

use App\Enums\AreaRole;
use App\Models\PanicAlert;
use App\Models\User;
use App\Notifications\PanicAlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PanicAlertNotifier
{
    public function __construct(
        private TwilioMessageSender $twilio,
        private AreaNotificationRecipients $recipients,
    ) {}

    public function notify(PanicAlert $alert): void
    {
        $alert->loadMissing([
            'user',
            'area',
            'patrolRun.round',
        ]);

        $appRecipients = $this->recipients->forArea($alert->area_id, $alert->user);

        if ($appRecipients->isNotEmpty()) {
            Notification::send($appRecipients, new PanicAlertNotification($alert));
        }

        $contacts = User::query()
            ->whereHas(
                'areas',
                fn ($query) => $query
                    ->where('areas.id', $alert->area_id)
                    ->where('area_user.role', AreaRole::Contact->value),
            )
            ->get();

        if ($contacts->isEmpty()) {
            Log::warning('Botón de pánico sin contactos en el área.', [
                'panic_alert_id' => $alert->id,
                'area_id' => $alert->area_id,
            ]);

            return;
        }

        $message = $this->buildMessage($alert);

        foreach ($contacts as $contact) {
            $this->twilio->notifyContact($contact, $message, 'Botón de pánico');
        }
    }

    private function buildMessage(PanicAlert $alert): string
    {
        $lines = [
            'RDI — BOTÓN DE PÁNICO',
            "Área: {$alert->area->name}",
            "Guardia: {$alert->user->name}",
            'Hora: '.$alert->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];

        if ($alert->patrolRun?->round) {
            $lines[] = "Recorrido: {$alert->patrolRun->round->title}";
        }

        return implode("\n", $lines);
    }
}
