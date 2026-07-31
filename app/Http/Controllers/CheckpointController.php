<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkpoint\ReorderCheckpointsRequest;
use App\Http\Requests\Checkpoint\StoreCheckpointRequest;
use App\Http\Requests\Checkpoint\UpdateCheckpointRequest;
use App\Models\Checkpoint;
use App\Models\Round;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckpointController extends Controller
{
    public function store(StoreCheckpointRequest $request, Round $round): RedirectResponse
    {
        $nextPosition = ((int) $round->checkpoints()->max('position')) + 1;

        $round->checkpoints()->create([
            'name' => $request->validated('name'),
            'instructions' => $request->validated('instructions'),
            'is_active' => $request->boolean('is_active', true),
            'position' => $nextPosition,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Punto de revisión creado.')]);

        return to_route('rounds.edit', $round);
    }

    public function update(UpdateCheckpointRequest $request, Checkpoint $checkpoint): RedirectResponse
    {
        $checkpoint->loadMissing('round');

        $checkpoint->update([
            'name' => $request->validated('name'),
            'instructions' => $request->validated('instructions'),
            'is_active' => $request->boolean('is_active'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Punto de revisión actualizado.')]);

        return to_route('rounds.edit', $checkpoint->round);
    }

    public function destroy(Checkpoint $checkpoint): RedirectResponse
    {
        $checkpoint->loadMissing('round');

        $this->authorize('update', $checkpoint->round);

        $round = $checkpoint->round;
        $checkpoint->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Punto de revisión eliminado.')]);

        return to_route('rounds.edit', $round);
    }

    public function reorder(ReorderCheckpointsRequest $request, Round $round): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('order') as $position => $checkpointId) {
                Checkpoint::query()
                    ->whereKey($checkpointId)
                    ->update(['position' => $position + 1]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Orden de puntos actualizado.')]);

        return to_route('rounds.edit', $round);
    }
}
