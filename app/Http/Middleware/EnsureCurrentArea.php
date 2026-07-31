<?php

namespace App\Http\Middleware;

use App\Models\Area;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentArea
{
    /**
     * Resolve and validate the current area context in the session.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $user->loadMissing('areas');

        $currentAreaId = $request->session()->get('current_area_id');

        if ($request->has('area_id')) {
            $requestedId = (int) $request->input('area_id');

            if ($user->isSuperAdmin() || $user->areas->contains('id', $requestedId)) {
                $request->session()->put('current_area_id', $requestedId);
                $currentAreaId = $requestedId;
            }
        }

        if ($currentAreaId) {
            $accessible = $user->isSuperAdmin()
                || $user->areas->contains('id', (int) $currentAreaId);

            if (! $accessible) {
                $request->session()->forget('current_area_id');
                $currentAreaId = null;
            }
        }

        if (! $currentAreaId) {
            if ($user->isSuperAdmin()) {
                $currentAreaId = Area::query()->where('is_active', true)->value('id');
            } else {
                $currentAreaId = $user->areas->firstWhere('is_active', true)?->id
                    ?? $user->areas->first()?->id;
            }

            if ($currentAreaId) {
                $request->session()->put('current_area_id', $currentAreaId);
            }
        }

        if ($currentAreaId) {
            $request->attributes->set('current_area_id', (int) $currentAreaId);
        }

        return $next($request);
    }
}
