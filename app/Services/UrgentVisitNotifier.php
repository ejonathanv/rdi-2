<?php

namespace App\Services;

use App\Models\PatrolCheckpointVisit;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrgentVisitNotifier
{
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
            $this->notifyContact($contact, $message);
        }
    }

    private function notifyContact(User $contact, string $message): void
    {
        if ($contact->phone === null || $contact->phone === '') {
            Log::warning('Contacto sin teléfono para alerta urgente.', [
                'contact_id' => $contact->id,
            ]);

            return;
        }

        if ($contact->notify_via_sms) {
            $this->sendSms($contact->phone, $message);
        }

        if ($contact->notify_via_whatsapp) {
            $this->sendWhatsApp($contact->phone, $message);
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

    private function sendSms(string $phone, string $message): void
    {
        $this->sendTwilioMessage(
            channel: 'sms',
            to: $this->formatE164($phone),
            from: config('twilio.sms_from'),
            body: $message,
        );
    }

    private function sendWhatsApp(string $phone, string $message): void
    {
        $from = config('twilio.whatsapp_from');

        if (! $from) {
            Log::warning('TWILIO_WHATSAPP_FROM no configurado; omitiendo WhatsApp.');

            return;
        }

        $this->sendTwilioMessage(
            channel: 'whatsapp',
            to: 'whatsapp:'.$this->formatE164($phone),
            from: str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from,
            body: $message,
        );
    }

    private function sendTwilioMessage(string $channel, string $to, ?string $from, string $body): void
    {
        $sid = config('twilio.account_sid');
        $token = config('twilio.auth_token');

        if (! $sid || ! $token || ! $from) {
            Log::info("Alerta urgente ({$channel}, simulada)", [
                'to' => $to,
                'body' => $body,
            ]);

            return;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $to,
                'From' => $from,
                'Body' => $body,
            ]);

        if ($response->failed()) {
            Log::error("Error al enviar alerta urgente por {$channel}.", [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $messageSid = $response->json('sid');

        Log::info("Alerta urgente aceptada por Twilio ({$channel}).", [
            'to' => $to,
            'sid' => $messageSid,
            'status' => $response->json('status'),
        ]);

        if (! is_string($messageSid) || $messageSid === '') {
            return;
        }

        // El sandbox puede aceptar el mensaje y fallar después (p. ej. 63015).
        usleep(1_500_000);

        $statusResponse = Http::withBasicAuth($sid, $token)
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages/{$messageSid}.json");

        if ($statusResponse->failed()) {
            return;
        }

        $finalStatus = $statusResponse->json('status');
        $errorCode = $statusResponse->json('error_code');

        if (in_array($finalStatus, ['failed', 'undelivered'], true)) {
            Log::error("Alerta urgente no entregada por {$channel}.", [
                'to' => $to,
                'sid' => $messageSid,
                'status' => $finalStatus,
                'error_code' => $errorCode,
                'error_message' => $statusResponse->json('error_message'),
            ]);

            return;
        }

        Log::info("Alerta urgente entregada/en curso por {$channel}.", [
            'to' => $to,
            'sid' => $messageSid,
            'status' => $finalStatus,
        ]);
    }

    private function formatE164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '52') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (strlen($digits) === 10) {
            return '+52'.$digits;
        }

        return str_starts_with($phone, '+') ? $phone : '+'.$digits;
    }
}
