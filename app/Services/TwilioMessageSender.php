<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioMessageSender
{
    public function notifyContact(User $contact, string $message, string $context = 'alerta'): void
    {
        if ($contact->phone === null || $contact->phone === '') {
            Log::warning("Contacto sin teléfono para {$context}.", [
                'contact_id' => $contact->id,
            ]);

            return;
        }

        if ($contact->notify_via_sms) {
            $this->sendSms($contact->phone, $message, $context);
        }

        if ($contact->notify_via_whatsapp) {
            $this->sendWhatsApp($contact->phone, $message, $context);
        }
    }

    private function sendSms(string $phone, string $message, string $context): void
    {
        $this->sendTwilioMessage(
            channel: 'sms',
            to: $this->formatE164($phone),
            from: config('twilio.sms_from'),
            body: $message,
            context: $context,
        );
    }

    private function sendWhatsApp(string $phone, string $message, string $context): void
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
            context: $context,
        );
    }

    private function sendTwilioMessage(
        string $channel,
        string $to,
        ?string $from,
        string $body,
        string $context,
    ): void {
        $sid = config('twilio.account_sid');
        $token = config('twilio.auth_token');

        if (! $sid || ! $token || ! $from) {
            Log::info("{$context} ({$channel}, simulada)", [
                'to' => $to,
                'body' => $body,
            ]);

            return;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->acceptJson()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $to,
                'From' => $from,
                'Body' => $body,
            ]);

        if ($response->failed()) {
            Log::error("Error al enviar {$context} por {$channel}.", [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $messageSid = $response->json('sid');

        Log::info("{$context} aceptada por Twilio ({$channel}).", [
            'to' => $to,
            'sid' => $messageSid,
            'status' => $response->json('status'),
        ]);

        if (! is_string($messageSid) || $messageSid === '') {
            return;
        }

        usleep(1_500_000);

        $statusResponse = Http::withBasicAuth($sid, $token)
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages/{$messageSid}.json");

        if ($statusResponse->failed()) {
            return;
        }

        $finalStatus = $statusResponse->json('status');
        $errorCode = $statusResponse->json('error_code');

        if (in_array($finalStatus, ['failed', 'undelivered'], true)) {
            Log::error("{$context} no entregada por {$channel}.", [
                'to' => $to,
                'sid' => $messageSid,
                'status' => $finalStatus,
                'error_code' => $errorCode,
                'error_message' => $statusResponse->json('error_message'),
            ]);

            return;
        }

        Log::info("{$context} entregada/en curso por {$channel}.", [
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
