<?php

namespace App\Http\Requests\Rondin;

use App\Models\Round;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexRondinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Round::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
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
        ];
    }
}
