<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\ReportDateRangeRequest;
use App\Models\Area;
use App\Services\OperationalReports;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private OperationalReports $reports) {}

    public function volumen(ReportDateRangeRequest $request): Response
    {
        [$area, $from, $to] = $this->resolveContext($request);

        return Inertia::render('reportes/volumen', [
            'area' => $area->only(['id', 'name', 'code']),
            'filters' => $request->filterPayload(),
            'report' => $this->reports->volumen($area, $from, $to),
        ]);
    }

    public function tiempos(ReportDateRangeRequest $request): Response
    {
        [$area, $from, $to] = $this->resolveContext($request);

        return Inertia::render('reportes/tiempos', [
            'area' => $area->only(['id', 'name', 'code']),
            'filters' => $request->filterPayload(),
            'report' => $this->reports->tiempos($area, $from, $to),
        ]);
    }

    public function puntosCriticos(ReportDateRangeRequest $request): Response
    {
        [$area, $from, $to] = $this->resolveContext($request);

        return Inertia::render('reportes/puntos-criticos', [
            'area' => $area->only(['id', 'name', 'code']),
            'filters' => $request->filterPayload(),
            'report' => [
                'rows' => $this->reports->puntosCriticos($area, $from, $to),
            ],
        ]);
    }

    /**
     * @return array{0: Area, 1: CarbonInterface, 2: CarbonInterface}
     */
    private function resolveContext(ReportDateRangeRequest $request): array
    {
        $user = $request->user();
        $area = $this->resolveCurrentArea($request);

        abort_unless($area && $user->canViewAreaOperations($area), 403);

        return [$area, $request->fromDate(), $request->toDate()];
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
