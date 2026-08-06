<?php

namespace App\Concerns;

use App\Enums\AreaRole;
use Illuminate\Validation\Validator;

trait ContactNotificationRules
{
    /**
     * @return array<string, mixed>
     */
    protected function contactNotificationRules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:20'],
            'notify_via_whatsapp' => ['sometimes', 'boolean'],
            'notify_via_sms' => ['sometimes', 'boolean'],
        ];
    }

    protected function validateContactNotification(Validator $validator): void
    {
        $memberships = $this->input('memberships', []);
        $hasContactRole = collect($memberships)->contains(
            fn (array $membership) => ($membership['role'] ?? null) === AreaRole::Contact->value,
        );

        if (! $hasContactRole) {
            return;
        }

        $notifyWhatsApp = $this->boolean('notify_via_whatsapp');
        $notifySms = $this->boolean('notify_via_sms');

        if (($notifyWhatsApp || $notifySms) && ! $this->filled('phone')) {
            $validator->errors()->add(
                'phone',
                __('El teléfono es obligatorio para contactos con notificaciones activas.'),
            );
        }

        if ($hasContactRole && ! $notifyWhatsApp && ! $notifySms) {
            $validator->errors()->add(
                'notify_via_whatsapp',
                __('Selecciona al menos un canal de notificación para el contacto.'),
            );
        }
    }
}
