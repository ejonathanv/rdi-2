<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrentAreaController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $user = $request->user();
        $area = Area::query()->findOrFail($validated['area_id']);

        abort_unless($user->canAccessArea($area), 403);

        $request->session()->put('current_area_id', $area->id);

        return back();
    }
}
