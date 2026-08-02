<?php

namespace App\Http\Requests\CheckpointQuestion;

use App\Models\Checkpoint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCheckpointQuestionRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'options.min' => __('Debes agregar al menos dos opciones de respuesta.'),
            'options.*.required' => __('Cada opción debe tener un texto.'),
        ];
    }
}
