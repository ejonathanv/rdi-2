<?php

namespace App\Http\Requests\PanicAlert;

use Illuminate\Foundation\Http\FormRequest;

class StorePanicAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasGuardRole() ?? false;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
