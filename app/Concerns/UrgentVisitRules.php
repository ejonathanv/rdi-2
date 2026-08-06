<?php

namespace App\Concerns;

trait UrgentVisitRules
{
    /**
     * @return array<string, mixed>
     */
    protected function urgentVisitRules(): array
    {
        return [
            'is_urgent' => ['sometimes', 'boolean'],
            'urgent_notes' => ['nullable', 'string', 'max:2000', 'required_if:is_urgent,true'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function urgentVisitMessages(): array
    {
        return [
            'urgent_notes.required_if' => __('Agrega notas cuando marques el punto como urgente.'),
            'urgent_notes.max' => __('Las notas no pueden superar los 2000 caracteres.'),
        ];
    }
}
