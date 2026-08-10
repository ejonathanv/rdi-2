<?php

namespace App\Services;

use App\Enums\AreaRole;
use App\Models\Incident;
use App\Models\User;
use App\Notifications\IncidentClosedAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class IncidentClosureNotifier
{
    public function __construct(
        private TwilioMessageSender $twilio,
        private AreaNotificationRecipients $recipients,
    ) {}

    public function notify(Incident $incident): void
    {
        $incident->loadMissing([
            'user',
            'resolvedBy',
            'category.contacts',
            'area',
            'checkpoint',
            'patrolRun.round',
        ]);

        $except = $incident->resolvedBy;
        $appRecipients = $this->recipients->forArea($incident->area_id, $except);

        if ($incident->user && (! $except || $incident->user->isNot($except))) {
            if (! $appRecipients->contains('id', $incident->user->id)) {
                $appRecipients->push($incident->user);
            }
        }

        if ($appRecipients->isNotEmpty()) {
            Notification::send($appRecipients->unique('id')->values(), new IncidentClosedAlert($incident));
        }

        $recipients = $this->resolveTwilioRecipients($incident);

        if ($recipients->isEmpty()) {
            Log::warning('Cierre de incidencia sin destinatarios Twilio.', [
                'incident_id' => $incident->id,
                'area_id' => $incident->area_id,
            ]);

            return;
        }

        $message = $this->buildMessage($incident);

        foreach ($recipients as $recipient) {
            $this->twilio->notifyContact($recipient, $message, 'Cierre de incidencia');
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveTwilioRecipients(Incident $incident): Collection
    {
        $recipients = collect();

        if ($incident->category !== null) {
            $categoryContacts = $incident->category->contacts;

            if ($categoryContacts->isNotEmpty()) {
                $recipients = $recipients->merge($categoryContacts);
            } else {
                $recipients = $recipients->merge(
                    User::query()
                        ->whereHas(
                            'areas',
                            fn ($query) => $query
                                ->where('areas.id', $incident->area_id)
                                ->where('area_user.role', AreaRole::Contact->value),
                        )
                        ->get(),
                );
            }
        } else {
            $recipients = $recipients->merge(
                User::query()
                    ->whereHas(
                        'areas',
                        fn ($query) => $query
                            ->where('areas.id', $incident->area_id)
                            ->where('area_user.role', AreaRole::Contact->value),
                    )
                    ->get(),
            );
        }

        $guard = $incident->user;

        if ($guard && ($guard->notify_via_sms || $guard->notify_via_whatsapp) && filled($guard->phone)) {
            $recipients->push($guard);
        }

        return $recipients->unique('id')->values();
    }

    private function buildMessage(Incident $incident): string
    {
        $lines = [
            'RDI — Incidencia '.$incident->status->label(),
            "Folio: #{$incident->id}",
            "Área: {$incident->area->name}",
        ];

        if ($incident->category) {
            $lines[] = "Categoría: {$incident->category->name}";
        }

        $lines[] = 'Cerrada por: '.($incident->resolvedBy?->name ?? '—');
        $lines[] = "Notas: {$incident->resolution_notes}";

        return implode("\n", $lines);
    }
}
