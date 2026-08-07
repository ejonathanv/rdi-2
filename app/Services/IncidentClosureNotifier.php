<?php

namespace App\Services;

use App\Enums\AreaRole;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class IncidentClosureNotifier
{
    public function __construct(private TwilioMessageSender $twilio) {}

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

        $recipients = $this->resolveRecipients($incident);

        if ($recipients->isEmpty()) {
            Log::warning('Cierre de incidencia sin destinatarios.', [
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
    private function resolveRecipients(Incident $incident): Collection
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
