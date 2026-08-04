<?php

namespace App\Http\Requests\CheckpointScan;

use App\Models\Checkpoint;
use Illuminate\Foundation\Http\FormRequest;

class MarkAllClearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkpoint = $this->checkpoint();

        return $this->user()?->canRespondToCheckpoint($checkpoint) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function checkpoint(): Checkpoint
    {
        return Checkpoint::query()
            ->where('token', $this->route('token'))
            ->where('is_active', true)
            ->with(['round.area'])
            ->firstOrFail();
    }
}
