<?php

namespace App\Http\Requests\GuardSync;

use App\Concerns\OfflineSyncRules;
use App\Models\PatrolRun;
use App\Models\Round;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncPatrolRequest extends FormRequest
{
    use OfflineSyncRules;

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
            'round_id' => ['required', 'integer'],
            'started_at' => $this->clientTimestampRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->existingPatrol()) {
                return;
            }

            $round = Round::query()->find((int) $this->input('round_id'));

            if (! $round || ! $round->is_active || ! $this->guardCanUseArea($round->area_id, $this->user()->guardAreaIds())) {
                $validator->errors()->add('round_id', __('El recorrido no está disponible.'));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->clientTimestampMessages('started_at');
    }

    public function existingPatrol(): ?PatrolRun
    {
        return PatrolRun::query()
            ->where('client_uuid', (string) $this->input('client_uuid'))
            ->where('user_id', $this->user()->id)
            ->first();
    }

    public function startedAt(): CarbonInterface
    {
        return $this->clientTimestamp('started_at');
    }
}
