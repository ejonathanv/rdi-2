<?php

namespace App\Http\Requests\CheckpointQuestion;

use App\Models\CheckpointQuestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckpointQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CheckpointQuestion $question */
        $question = $this->route('question');
        $question->loadMissing('checkpoint.round.area');

        return $this->user()?->can('update', $question->checkpoint->round) ?? false;
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
