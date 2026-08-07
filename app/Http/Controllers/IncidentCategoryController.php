<?php

namespace App\Http\Controllers;

use App\Enums\AreaRole;
use App\Http\Requests\IncidentCategory\StoreIncidentCategoryRequest;
use App\Http\Requests\IncidentCategory\UpdateIncidentCategoryRequest;
use App\Models\Area;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IncidentCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', IncidentCategory::class);

        $user = $request->user();
        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $user->canManageArea($currentArea), 403);

        $categories = IncidentCategory::query()
            ->where('area_id', $currentArea->id)
            ->withCount('contacts')
            ->orderBy('name')
            ->get();

        return Inertia::render('incident-categories/index', [
            'area' => $currentArea->only(['id', 'name', 'code']),
            'categories' => $categories,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', IncidentCategory::class);

        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $request->user()->canManageArea($currentArea), 403);

        return Inertia::render('incident-categories/create', [
            'area' => $currentArea->only(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreIncidentCategoryRequest $request): RedirectResponse
    {
        $category = IncidentCategory::query()->create([
            'area_id' => $request->validated('area_id'),
            'name' => $request->validated('name'),
            'code' => $request->validated('code'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Categoría creada.')]);

        return to_route('incident-categories.edit', $category);
    }

    public function edit(Request $request, IncidentCategory $incidentCategory): Response
    {
        $this->authorize('update', $incidentCategory);

        $incidentCategory->load(['area:id,name,code', 'contacts:id,name,email,phone']);

        return Inertia::render('incident-categories/edit', [
            'area' => $incidentCategory->area->only(['id', 'name', 'code']),
            'category' => [
                'id' => $incidentCategory->id,
                'name' => $incidentCategory->name,
                'code' => $incidentCategory->code,
                'description' => $incidentCategory->description,
                'is_active' => $incidentCategory->is_active,
            ],
            'availableContacts' => $this->availableContactsFor($incidentCategory),
            'assignedContactIds' => $incidentCategory->contacts->pluck('id')->all(),
        ]);
    }

    public function update(
        UpdateIncidentCategoryRequest $request,
        IncidentCategory $incidentCategory,
    ): RedirectResponse {
        $incidentCategory->update([
            'name' => $request->validated('name'),
            'code' => $request->validated('code'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $incidentCategory->contacts()->sync($request->validated('contact_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Categoría actualizada.')]);

        return to_route('incident-categories.edit', $incidentCategory);
    }

    public function destroy(IncidentCategory $incidentCategory): RedirectResponse
    {
        $this->authorize('delete', $incidentCategory);

        $incidentCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Categoría eliminada.')]);

        return to_route('incident-categories.index');
    }

    private function resolveCurrentArea(Request $request): ?Area
    {
        $areaId = $request->attributes->get('current_area_id')
            ?? $request->session()->get('current_area_id');

        if (! $areaId) {
            return null;
        }

        return Area::query()->find((int) $areaId);
    }

    /**
     * @return list<array{id: int, name: string, email: string, phone: string|null}>
     */
    private function availableContactsFor(IncidentCategory $category): array
    {
        return User::query()
            ->whereHas('areas', fn ($query) => $query
                ->where('areas.id', $category->area_id)
                ->where('role', AreaRole::Contact->value))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ])
            ->all();
    }
}
