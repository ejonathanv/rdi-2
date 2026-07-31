<?php

namespace App\Http\Controllers;

use App\Http\Requests\Round\StoreRoundRequest;
use App\Http\Requests\Round\UpdateRoundRequest;
use App\Models\Area;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoundController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Round::class);

        $user = $request->user();
        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $user->canManageArea($currentArea), 403);

        $rounds = Round::query()
            ->where('area_id', $currentArea->id)
            ->withCount('checkpoints')
            ->orderBy('title')
            ->get();

        return Inertia::render('rounds/index', [
            'area' => $currentArea->only(['id', 'name', 'code']),
            'rounds' => $rounds,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Round::class);

        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $request->user()->canManageArea($currentArea), 403);

        return Inertia::render('rounds/create', [
            'area' => $currentArea->only(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreRoundRequest $request): RedirectResponse
    {
        $round = Round::query()->create([
            'area_id' => $request->validated('area_id'),
            'title' => $request->validated('title'),
            'instructions' => $request->validated('instructions'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recorrido creado.')]);

        return to_route('rounds.edit', $round);
    }

    public function edit(Request $request, Round $round): Response
    {
        $this->authorize('update', $round);

        $round->load(['area:id,name,code', 'checkpoints']);

        return Inertia::render('rounds/edit', [
            'area' => $round->area->only(['id', 'name', 'code']),
            'round' => [
                'id' => $round->id,
                'title' => $round->title,
                'instructions' => $round->instructions,
                'is_active' => $round->is_active,
                'checkpoints' => $round->checkpoints->map(fn ($checkpoint) => [
                    'id' => $checkpoint->id,
                    'name' => $checkpoint->name,
                    'instructions' => $checkpoint->instructions,
                    'position' => $checkpoint->position,
                    'token' => $checkpoint->token,
                    'is_active' => $checkpoint->is_active,
                ]),
            ],
        ]);
    }

    public function update(UpdateRoundRequest $request, Round $round): RedirectResponse
    {
        $round->update([
            'title' => $request->validated('title'),
            'instructions' => $request->validated('instructions'),
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recorrido actualizado.')]);

        return to_route('rounds.edit', $round);
    }

    public function destroy(Round $round): RedirectResponse
    {
        $this->authorize('delete', $round);

        $round->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Recorrido eliminado.')]);

        return to_route('rounds.index');
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
}
