<?php

namespace App\Http\Controllers;

use App\Models\Round;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuardRoundController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->hasGuardRole(), 403);

        $areaIds = $user->guardAreaIds();

        $rounds = Round::query()
            ->whereIn('area_id', $areaIds)
            ->where('is_active', true)
            ->with(['area:id,name'])
            ->withCount('checkpoints')
            ->orderBy('title')
            ->get()
            ->map(fn (Round $round) => [
                'id' => $round->id,
                'title' => $round->title,
                'instructions' => $round->instructions,
                'checkpoints_count' => $round->checkpoints_count,
                'area' => [
                    'id' => $round->area->id,
                    'name' => $round->area->name,
                ],
            ])
            ->values()
            ->all();

        return Inertia::render('guard/rounds/index', [
            'rounds' => $rounds,
        ]);
    }

    public function show(Request $request, Round $round): Response
    {
        $user = $request->user();
        abort_unless($user?->hasGuardRole(), 403);
        abort_unless(in_array($round->area_id, $user->guardAreaIds(), true), 403);
        abort_unless($round->is_active, 404);

        $round->load([
            'area:id,name',
            'checkpoints' => fn ($query) => $query->where('is_active', true)->orderBy('position'),
        ]);

        return Inertia::render('guard/rounds/show', [
            'round' => [
                'id' => $round->id,
                'title' => $round->title,
                'instructions' => $round->instructions,
                'area' => [
                    'id' => $round->area->id,
                    'name' => $round->area->name,
                ],
                'checkpoints' => $round->checkpoints->map(fn ($checkpoint) => [
                    'id' => $checkpoint->id,
                    'name' => $checkpoint->name,
                    'instructions' => $checkpoint->instructions,
                    'position' => $checkpoint->position,
                ])->values()->all(),
            ],
        ]);
    }
}
