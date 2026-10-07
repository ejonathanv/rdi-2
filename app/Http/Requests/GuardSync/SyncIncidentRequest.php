<?php

namespace App\Http\Requests\GuardSync;

use App\Concerns\OfflineSyncRules;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\PatrolRun;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncIncidentRequest extends FormRequest
{
    use OfflineSyncRules;

    private ?PatrolRun $patrol = null;

    private ?Checkpoint $checkpoint = null;

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
            'visit_client_uuid' => ['nullable', 'uuid'],
            'area_id' => ['nullable', 'integer', 'required_without:checkpoint_token'],
            'message' => ['required', 'string', 'max:5000'],
            'is_urgent' => ['sometimes', 'boolean'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'checkpoint_token' => ['nullable', 'string'],
            'patrol_client_uuid' => ['nullable', 'uuid', 'required_with:checkpoint_token'],
            'occurred_at' => $this->clientTimestampRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->existingIncident()) {
                return;
            }

            $token = $this->input('checkpoint_token');

            if (! is_string($token) || $token === '') {
                $area = Area::query()->find((int) $this->input('area_id'));

                if (! $area || ! $this->guardCanUseArea($area->id, $this->user()->guardAreaIds())) {
                    $validator->errors()->add('area_id', __('No puedes reportar en esta área.'));

                    return;
                }

                $this->area = $area;

                return;
            }

            $patrol = $this->ownPatrolOrConflict((string) $this->input('patrol_client_uuid'));

            $checkpoint = Checkpoint::query()
                ->where('token', $token)
                ->where('round_id', $patrol->round_id)
                ->with('round.area')
                ->first();

            if (! $checkpoint) {
                $validator->errors()->add('checkpoint_token', __('El punto no pertenece a este recorrido.'));

                return;
            }

            if (! $patrol->isInProgress() || $patrol->visits()->where('checkpoint_id', $checkpoint->id)->exists()) {
                $validator->errors()->add('checkpoint_token', __('Este punto ya fue revisado en el recorrido actual.'));

                return;
            }

            $this->patrol = $patrol;
            $this->checkpoint = $checkpoint;
            $this->area = $checkpoint->round->area;
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => __('Puedes adjuntar máximo :max fotos.'),
            ...$this->clientTimestampMessages('occurred_at'),
        ];
    }

    public function existingIncident(): ?Incident
    {
        return Incident::query()
            ->where('client_uuid', (string) $this->input('client_uuid'))
            ->where('user_id', $this->user()->id)
            ->first();
    }

    public function area(): Area
    {
        return $this->area ?? throw new \LogicException('Area not resolved.');
    }

    public function patrol(): ?PatrolRun
    {
        return $this->patrol;
    }

    public function checkpoint(): ?Checkpoint
    {
        return $this->checkpoint;
    }

    public function occurredAt(): CarbonInterface
    {
        return $this->clientTimestamp('occurred_at');
    }
}
