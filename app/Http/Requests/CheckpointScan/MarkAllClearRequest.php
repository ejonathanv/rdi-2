<?php

namespace App\Http\Requests\CheckpointScan;

use App\Models\Checkpoint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarkAllClearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $checkpoint = $this->checkpoint();

        return $this->user()?->canRespondToCheckpoint($checkpoint) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => __('Puedes adjuntar máximo :max fotos.'),
            'photos.*.image' => __('Cada archivo debe ser una imagen.'),
            'photos.*.mimes' => __('Las fotos deben ser JPEG, PNG o WebP.'),
            'photos.*.max' => __('Cada foto no puede superar los 10 MB.'),
            'photos.*.uploaded' => __('No se pudo subir la foto. Prueba con una imagen más pequeña.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'photos' => __('fotos'),
            'photos.*' => __('foto'),
        ];
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
