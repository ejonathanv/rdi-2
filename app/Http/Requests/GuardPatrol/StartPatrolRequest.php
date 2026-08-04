<?php

namespace App\Http\Requests\GuardPatrol;

use App\Models\Round;
use Illuminate\Foundation\Http\FormRequest;

class StartPatrolRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Round $round */
        $round = $this->route('round');
        $user = $this->user();

        if (! $user?->hasGuardRole()) {
            return false;
        }

        return in_array($round->area_id, $user->guardAreaIds(), true) && $round->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
