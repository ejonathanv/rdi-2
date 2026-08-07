<?php

namespace App\Http\Requests\IncidentCategory;

use App\Models\Area;
use App\Models\IncidentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreIncidentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', IncidentCategory::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge([
                'code' => Str::upper(Str::slug((string) $this->input('code'), '_')),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $areaId = (int) $this->input('area_id');

        return [
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('incident_categories', 'code')->where('area_id', $areaId),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $areaId = (int) $this->input('area_id');

            if (! $user || ! $areaId) {
                return;
            }

            $area = Area::query()->find($areaId);

            if (! $area || ! $user->canManageArea($area)) {
                $validator->errors()->add('area_id', __('No puedes crear categorías en esta área.'));
            }
        });
    }
}
