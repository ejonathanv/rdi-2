<?php

namespace App\Http\Requests\IncidentCategory;

use App\Models\Area;
use App\Models\IncidentCategory;
use Illuminate\Foundation\Http\FormRequest;

class ImportDefaultIncidentCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $area = $this->resolvedArea();

        return $user !== null
            && $area !== null
            && $user->can('create', IncidentCategory::class)
            && $user->canManageArea($area);
    }

    /**
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }

    public function area(): Area
    {
        $area = $this->resolvedArea();

        abort_unless($area instanceof Area, 403);

        return $area;
    }

    private function resolvedArea(): ?Area
    {
        $areaId = $this->attributes->get('current_area_id')
            ?? $this->session()->get('current_area_id');

        if (! $areaId) {
            return null;
        }

        return Area::query()->find((int) $areaId);
    }
}
