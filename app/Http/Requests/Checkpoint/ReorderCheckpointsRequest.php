<?php

namespace App\Http\Requests\Checkpoint;

use App\Models\Round;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderCheckpointsRequest extends FormRequest
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
        /** @var Round $round */
        $round = $this->route('round');

        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => [
                'integer',
                Rule::exists('checkpoints', 'id')->where('round_id', $round->id),
            ],
        ];
    }
}
