<?php

namespace App\Http\Requests\Checkpoint;

use App\Models\Checkpoint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Checkpoint $checkpoint */
        $checkpoint = $this->route('checkpoint');
        $checkpoint->loadMissing('round.area');

        return $this->user()?->can('update', $checkpoint->round) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
