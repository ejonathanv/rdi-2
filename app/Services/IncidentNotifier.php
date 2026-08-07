<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\Log;

class IncidentNotifier
{
    public function __construct(private TwilioMessageSender $twilio) {}

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

        $contacts = $incident->category->contacts;

        if ($contacts->isEmpty()) {
            Log::warning('Categoría de incidencia sin contactos asignados.', [
                'incident_id' => $incident->id,
                'category_id' => $incident->category->id,
            ]);

            return;
        }

        $message = $this->buildMessage($incident);

        foreach ($contacts as $contact) {
            $this->twilio->notifyContact($contact, $message, 'Alerta de incidencia');
        }
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
