<?php

namespace App\Http\Middleware;

use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing('areas');
        }

        $currentAreaId = $request->attributes->get('current_area_id')
            ?? $request->session()->get('current_area_id');

        $currentArea = null;
        $availableAreas = [];

        if ($user) {
            if ($user->isSuperAdmin()) {
                $availableAreas = Area::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'code', 'is_active'])
                    ->all();
            } else {
                $availableAreas = $user->areas
                    ->sortBy('name')
                    ->values()
                    ->map(fn (Area $area) => [
                        'id' => $area->id,
                        'name' => $area->name,
                        'code' => $area->code,
                        'is_active' => $area->is_active,
                        'role' => $area->pivot->role instanceof \BackedEnum
                            ? $area->pivot->role->value
                            : $area->pivot->role,
                    ])
                    ->all();
            }

            if ($currentAreaId) {
                $currentArea = collect($availableAreas)->firstWhere('id', (int) $currentAreaId);
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'is_guard_only' => $user->isGuardOnly(),
                    'home_path' => $user->homePath(),
                    'can_manage_areas' => $user->canManageAnyArea(),
                    'can_manage_users' => $user->canManageAnyArea(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ] : null,
            ],
            'currentArea' => $currentArea,
            'availableAreas' => $availableAreas,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
