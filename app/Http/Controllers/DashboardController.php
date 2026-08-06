<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Services\AdminDashboardStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private AdminDashboardStats $dashboardStats) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->isGuardOnly()) {
            return redirect()->route('guard.home');
        }

        $area = $this->resolveCurrentArea($request);

        return Inertia::render('dashboard', $this->dashboardStats->forArea($area));
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
