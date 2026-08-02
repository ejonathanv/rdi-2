<?php

namespace App\Http\Requests\CheckpointQuestion;

use App\Models\Checkpoint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderCheckpointQuestionsRequest extends FormRequest
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
        /** @var Checkpoint $checkpoint */
        $checkpoint = $this->route('checkpoint');

        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => [
                'integer',
                Rule::exists('checkpoint_questions', 'id')->where('checkpoint_id', $checkpoint->id),
            ],
        ];
    }
}
