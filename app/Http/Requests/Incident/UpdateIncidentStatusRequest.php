<?php

namespace App\Http\Requests\Incident;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Incident $incident */
        $incident = $this->route('incident');

        return $this->user()?->can('update', $incident) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(IncidentStatus::class)],
            'resolution_notes' => [
                'nullable',
                'string',
                'max:5000',
                Rule::requiredIf(fn () => in_array(
                    $this->input('status'),
                    [IncidentStatus::Resuelta->value, IncidentStatus::Descartada->value],
                    true,
                )),
            ],
        ];
    }
}
