<?php

namespace App\Http\Requests\Incident;

use App\Models\Checkpoint;
use App\Models\PatrolRun;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreIncidentRequest extends FormRequest
{
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
            'message' => ['required', 'string', 'max:5000'],
            'is_urgent' => ['sometimes', 'boolean'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'checkpoint_token' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $token = $this->input('checkpoint_token');

            if (! is_string($token) || $token === '') {
                return;
            }

            $checkpoint = Checkpoint::query()
                ->where('token', $token)
                ->where('is_active', true)
                ->with('round')
                ->first();

            if (! $checkpoint || ! $checkpoint->round->is_active) {
                $validator->errors()->add('checkpoint_token', __('Punto de revisión no válido.'));

                return;
            }

            if (! $this->user()?->canRespondToCheckpoint($checkpoint)) {
                $validator->errors()->add('checkpoint_token', __('No puedes reportar en este punto.'));

                return;
            }

            $patrolId = $this->session()->get('active_patrol_run_id');
            $patrol = $patrolId
                ? PatrolRun::query()
                    ->whereKey($patrolId)
                    ->where('user_id', $this->user()->id)
                    ->first()
                : null;

            if (! $patrol || ! $patrol->isInProgress() || $patrol->round_id !== $checkpoint->round_id) {
                $validator->errors()->add(
                    'checkpoint_token',
                    __('Debes tener un recorrido activo para reportar en este punto.'),
                );

                return;
            }

            if ($patrol->visits()->where('checkpoint_id', $checkpoint->id)->exists()) {
                $validator->errors()->add(
                    'checkpoint_token',
                    __('Este punto ya fue revisado en el recorrido actual.'),
                );
            }
        });
    }
}
