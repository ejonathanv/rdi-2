<?php

namespace App\Http\Requests\Branding;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hexColor = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];

        return [
            'primary_color' => $hexColor,
            'light_color' => $hexColor,
            'dark_color' => $hexColor,
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'primary_color' => 'color primario',
            'light_color' => 'color claro',
            'dark_color' => 'color oscuro',
            'logo' => 'logotipo',
            'favicon' => 'favicon',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'primary_color.regex' => 'El color primario debe tener formato hexadecimal (#RRGGBB).',
            'light_color.regex' => 'El color claro debe tener formato hexadecimal (#RRGGBB).',
            'dark_color.regex' => 'El color oscuro debe tener formato hexadecimal (#RRGGBB).',
        ];
    }
}
