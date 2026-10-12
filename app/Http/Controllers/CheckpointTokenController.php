<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Round;
use App\Services\CheckpointTokenRotator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckpointTokenController extends Controller
{
    public function __construct(private CheckpointTokenRotator $rotator) {}

    public function regenerate(Round $round): RedirectResponse
    {
        $this->authorize('update', $round);

        $count = $this->rotator->rotate(Checkpoint::query()->where('round_id', $round->id));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(
                '{0} El recorrido no tiene puntos.|{1} Se regeneró el QR de :count punto. Imprime y coloca el nuevo.|[2,*] Se regeneraron los QR de :count puntos. Imprime y coloca los nuevos.',
                $count,
            ),
        ]);

        return back();
    }

    public function regenerateAll(Request $request): RedirectResponse
    {
        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $request->user()->canManageArea($currentArea), 403);

        $count = $this->rotator->rotate(
            Checkpoint::query()->whereHas('round', fn ($query) => $query->where('area_id', $currentArea->id)),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(
                '{0} No hay puntos en los recorridos del área.|{1} Se regeneró el QR de :count punto del área.|[2,*] Se regeneraron los QR de :count puntos del área.',
                $count,
            ),
        ]);

        return back();
    }

    public function print(Round $round): Response
    {
        $this->authorize('update', $round);

        $round->load('area:id,name,code');

        return Inertia::render('rounds/print-qr', [
            'area' => $round->area->only(['id', 'name', 'code']),
            'round' => [
                'id' => $round->id,
                'title' => $round->title,
            ],
            'checkpoints' => $round->checkpoints()
                ->where('is_active', true)
                ->orderBy('position')
                ->get(['id', 'name', 'position', 'token'])
                ->map(fn (Checkpoint $checkpoint) => [
                    'id' => $checkpoint->id,
                    'name' => $checkpoint->name,
                    'position' => $checkpoint->position,
                    'scan_url' => route('checkpoints.scan.show', $checkpoint->token),
                ]),
            'tokensRotatedAt' => $round->checkpoints()
                ->whereNotNull('token_rotated_at')
                ->latest('token_rotated_at')
                ->first()
                ?->token_rotated_at
                ?->toIso8601String(),
        ]);
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
