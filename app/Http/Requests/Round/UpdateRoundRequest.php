<?php

namespace App\Http\Requests\Round;

use App\Models\Round;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Round $round */
        $round = $this->route('round');

        return $this->user()?->can('update', $round) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'contact_ids' => ['nullable', 'array'],
            'contact_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }
}
