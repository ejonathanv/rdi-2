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
            'is_urgent' => ['nullable', 'boolean'],
            'open' => ['nullable', 'boolean'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('incident_categories', 'id')->where(
                    fn ($query) => $query->where('area_id', $areaId),
                ),
            ],
            'checkpoint_id' => [
                'nullable',
                'integer',
                Rule::exists('checkpoints', 'id')->where(function ($query) use ($areaId) {
                    $query->whereIn('round_id', function ($subquery) use ($areaId) {
                        $subquery->select('id')
                            ->from('rounds')
                            ->where('area_id', $areaId);
                    });
                }),
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
            'checkpoint_id' => 'punto',
            'status' => 'estado',
            'is_urgent' => 'urgente',
            'open' => 'abiertas',
        ];
    }
}
