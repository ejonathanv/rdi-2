<?php

namespace App\Services;

use App\Enums\AreaRole;
use App\Models\Incident;
use App\Models\User;
use App\Notifications\IncidentCreatedAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class IncidentNotifier
{
    public function __construct(
        private TwilioMessageSender $twilio,
        private AreaNotificationRecipients $recipients,
    ) {}

    public function notify(Incident $incident): void
    {
        $incident->loadMissing([
            'user',
            'category.contacts',
            'checkpoint',
            'patrolRun.round',
            'area',
        ]);

        if ($incident->category === null) {
            Log::info('Incidencia sin categoría; no se notifica.', [
                'incident_id' => $incident->id,
            ]);

            return;
        }

        $appRecipients = $this->recipients->forArea($incident->area_id, $incident->user);

        if ($appRecipients->isNotEmpty()) {
            Notification::send($appRecipients, new IncidentCreatedAlert($incident));
        }

        $contacts = $this->resolveContacts($incident);

        if ($contacts->isEmpty()) {
            Log::warning('Incidencia sin contactos de categoría ni del área.', [
                'incident_id' => $incident->id,
                'category_id' => $incident->category->id,
                'area_id' => $incident->area_id,
            ]);

            return;
        }

        $message = $this->buildMessage($incident);

        foreach ($contacts as $contact) {
            $this->twilio->notifyContact($contact, $message, 'Alerta de incidencia');
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveContacts(Incident $incident): Collection
    {
        $categoryContacts = $incident->category->contacts;

        if ($categoryContacts->isNotEmpty()) {
            return $categoryContacts;
        }

        Log::info('Categoría sin contactos; se notifica a contactos del área.', [
            'incident_id' => $incident->id,
            'category_id' => $incident->category->id,
            'area_id' => $incident->area_id,
        ]);

        return User::query()
            ->whereHas(
                'areas',
                fn ($query) => $query
                    ->where('areas.id', $incident->area_id)
                    ->where('area_user.role', AreaRole::Contact->value),
            )
            ->get();
    }

    private function buildMessage(Incident $incident): string
    {
        $lines = [
            $incident->is_urgent
                ? 'RDI — INCIDENCIA URGENTE'
                : 'RDI — Incidencia registrada',
            "Área: {$incident->area->name}",
            "Categoría: {$incident->category->name}",
            "Guardia: {$incident->user->name}",
        ];

        if ($incident->checkpoint) {
            $lines[] = "Punto: {$incident->checkpoint->name}";
        }

        if ($incident->patrolRun?->round) {
            $lines[] = "Recorrido: {$incident->patrolRun->round->title}";
        }

        $body = $incident->message_cleaned ?: $incident->message_raw;
        $lines[] = "Detalle: {$body}";

        return implode("\n", $lines);
    }
}
