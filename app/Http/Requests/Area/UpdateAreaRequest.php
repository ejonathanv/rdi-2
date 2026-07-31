<?php

namespace App\Http\Requests\Area;

use App\Models\Area;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Area $area */
        $area = $this->route('area');

        return $this->user()?->can('update', $area) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Area $area */
        $area = $this->route('area');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('areas', 'code')->ignore($area->id)],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
