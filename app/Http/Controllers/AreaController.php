<?php

namespace App\Http\Controllers;

use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Area::class);

        $user = $request->user();

        $areas = Area::query()
            ->when(
                ! $user->isSuperAdmin(),
                fn ($query) => $query->whereIn('id', $user->areas->pluck('id')),
            )
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return Inertia::render('areas/index', [
            'areas' => $areas,
            'canCreate' => $user->can('create', Area::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Area::class);

        return Inertia::render('areas/create');
    }

    public function store(StoreAreaRequest $request): RedirectResponse
    {
        Area::query()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Area created.')]);

        return to_route('areas.index');
    }

    public function edit(Area $area): Response
    {
        $this->authorize('update', $area);

        return Inertia::render('areas/edit', [
            'area' => $area,
        ]);
    }

    public function update(UpdateAreaRequest $request, Area $area): RedirectResponse
    {
        $area->update([
            ...$request->safe()->except('is_active'),
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Area updated.')]);

        return to_route('areas.index');
    }

    public function destroy(Request $request, Area $area): RedirectResponse
    {
        $this->authorize('delete', $area);

        $area->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Area deleted.')]);

        return to_route('areas.index');
    }
}
