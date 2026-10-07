<?php

namespace App\Http\Requests\GuardSync;

use App\Concerns\OfflineSyncRules;
use App\Models\Area;
use App\Models\PanicAlert;
use App\Models\PatrolRun;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncPanicAlertRequest extends FormRequest
{
    use OfflineSyncRules;

    private ?Area $area = null;

    public function authorize(): bool
    {
        return $this->user()?->hasGuardRole() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_uuid' => ['required', 'uuid'],
            'area_id' => ['required', 'integer'],
            'patrol_client_uuid' => ['nullable', 'uuid'],
            'occurred_at' => $this->clientTimestampRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->existingAlert()) {
                return;
            }

            $area = Area::query()->find((int) $this->input('area_id'));

            if (! $area || ! $this->guardCanUseArea($area->id, $this->user()->guardAreaIds())) {
                $validator->errors()->add('area_id', __('No puedes enviar alertas en esta área.'));

                return;
            }

            $this->area = $area;
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->clientTimestampMessages('occurred_at');
    }

    public function existingAlert(): ?PanicAlert
    {
        return PanicAlert::query()
            ->where('client_uuid', (string) $this->input('client_uuid'))
            ->where('user_id', $this->user()->id)
            ->first();
    }

    public function area(): Area
    {
        return $this->area ?? throw new \LogicException('Area not resolved.');
    }

    /**
     * Patrol is optional context; an unsynced patrol must not delay the alert.
     */
    public function patrol(): ?PatrolRun
    {
        $clientUuid = $this->input('patrol_client_uuid');

        if (! is_string($clientUuid) || $clientUuid === '') {
            return null;
        }

        return PatrolRun::query()
            ->where('client_uuid', $clientUuid)
            ->where('user_id', $this->user()->id)
            ->first();
    }

    public function occurredAt(): CarbonInterface
    {
        return $this->clientTimestamp('occurred_at');
    }
}
