<?php

namespace App\Services;

use App\Models\PatrolCheckpointVisit;
use Illuminate\Support\Facades\Log;

class UrgentVisitNotifier
{
    public function __construct(private TwilioMessageSender $twilio) {}

    public function notify(PatrolCheckpointVisit $visit): void
    {
        if (! $visit->is_urgent) {
            return;
        }

        $visit->loadMissing([
            'checkpoint',
            'patrolRun.user',
            'patrolRun.round.contacts',
        ]);

        $message = $this->buildMessage($visit);

        $contacts = $visit->patrolRun->round->contacts;

        if ($contacts->isEmpty()) {
            Log::warning('Punto urgente sin contactos asignados al recorrido.', [
                'visit_id' => $visit->id,
                'round_id' => $visit->patrolRun->round_id,
            ]);

            return;
        }

        foreach ($contacts as $contact) {
            $this->twilio->notifyContact($contact, $message, 'Alerta urgente de punto');
        }
    }

    private function buildMessage(PatrolCheckpointVisit $visit): string
    {
        $round = $visit->patrolRun->round;
        $guard = $visit->patrolRun->user;
        $checkpoint = $visit->checkpoint;

        $lines = [
            'RDI — Punto urgente de revisión',
            "Recorrido: {$round->title}",
            "Punto: {$checkpoint->name}",
            "Guardia: {$guard->name}",
            "Resultado: {$visit->outcome->label()}",
        ];

        if ($visit->urgent_notes) {
            $lines[] = "Notas: {$visit->urgent_notes}";
        }

        return implode("\n", $lines);
    }
}
