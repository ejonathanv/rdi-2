<?php

namespace App\Http\Requests\Round;

use App\Models\Area;
use App\Models\Round;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Round::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
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
                $validator->errors()->add('area_id', __('No puedes crear recorridos en esta área.'));
            }
        });
    }
}
