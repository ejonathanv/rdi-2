<?php

namespace App\Http\Requests\Incident;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIncidentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Incident::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $areaId = $this->attributes->get('current_area_id')
            ?? $this->session()->get('current_area_id');

        return [
            'status' => ['nullable', 'string', Rule::enum(IncidentStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('incident_categories', 'id')->where(
                    fn ($query) => $query->where('area_id', $areaId),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from' => 'fecha desde',
            'to' => 'fecha hasta',
            'category_id' => 'categoría',
            'status' => 'estado',
        ];
    }
}
