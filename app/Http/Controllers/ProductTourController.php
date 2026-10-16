<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductTourController extends Controller
{
    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isEligibleForProductTour(), 403);

        $user->markProductTourCompleted();

        return back();
    }
}
