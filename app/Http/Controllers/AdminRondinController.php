<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Services\PatrolReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AdminRondinController extends Controller
{
    public function __construct(private PatrolReportBuilder $reportBuilder) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Round::class);

        $user = $request->user();
        $currentArea = $this->resolveCurrentArea($request);

        abort_unless($currentArea && $user->canManageArea($currentArea), 403);

        $rounds = Round::query()
            ->where('area_id', $currentArea->id)
            ->withCount(['checkpoints', 'patrolRuns'])
            ->orderBy('title')
            ->get()
            ->map(fn (Round $round) => [
                'id' => $round->id,
                'title' => $round->title,
                'is_active' => $round->is_active,
                'checkpoints_count' => $round->checkpoints_count,
                'patrol_runs_count' => $round->patrol_runs_count,
            ]);

        return Inertia::render('rondines/index', [
            'area' => $currentArea->only(['id', 'name', 'code']),
            'rounds' => $rounds,
        ]);
    }

    public function showRound(Request $request, Round $round): Response
    {
        $this->authorize('view', $round);

        $round->load('area:id,name,code');

        return Inertia::render('rondines/round', [
            'area' => $round->area->only(['id', 'name', 'code']),
            'round' => [
                'id' => $round->id,
                'title' => $round->title,
                'is_active' => $round->is_active,
            ],
            'patrols' => $this->reportBuilder->summarizeRuns($round),
        ]);
    }

    public function showPatrol(Request $request, Round $round, PatrolRun $patrol): Response
    {
        $this->authorizePatrol($round, $patrol);

        $report = $this->reportBuilder->build($patrol);

        return Inertia::render('rondines/patrol', [
            ...$report,
            'pdf_url' => route('rondines.patrols.pdf', [$round, $patrol]),
        ]);
    }

    public function downloadPdf(Request $request, Round $round, PatrolRun $patrol): SymfonyResponse
    {
        $this->authorizePatrol($round, $patrol);

        $report = $this->reportBuilder->build($patrol);

        $checkpoints = collect($report['checkpoints'])->map(function (array $checkpoint) {
            $checkpoint['photos'] = collect($checkpoint['photos'])->map(function (array $photo) {
                $absolute = Storage::disk('public')->path($photo['path']);

                $photo['file_path'] = is_file($absolute) ? $absolute : null;

                return $photo;
            })->all();

            return $checkpoint;
        })->all();

        $pdf = Pdf::loadView('pdf.patrol-report', [
            'area' => $report['area'],
            'round' => $report['round'],
            'patrol' => $report['patrol'],
            'checkpoints' => $checkpoints,
        ])->setPaper('a4');

        $filename = sprintf(
            'rondin-%s-%s.pdf',
            $round->id,
            $patrol->id,
        );

        return $pdf->download($filename);
    }

    private function authorizePatrol(Round $round, PatrolRun $patrol): void
    {
        $this->authorize('view', $round);

        abort_unless($patrol->round_id === $round->id, 404);
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
