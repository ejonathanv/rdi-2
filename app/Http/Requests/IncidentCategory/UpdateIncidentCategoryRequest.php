<?php

namespace App\Http\Requests\IncidentCategory;

use App\Models\IncidentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateIncidentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var IncidentCategory $category */
        $category = $this->route('incident_category');

        return $this->user()?->can('update', $category) ?? false;
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
        /** @var IncidentCategory $category */
        $category = $this->route('incident_category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('incident_categories', 'code')
                    ->where('area_id', $category->area_id)
                    ->ignore($category->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'contact_ids' => ['nullable', 'array'],
            'contact_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
