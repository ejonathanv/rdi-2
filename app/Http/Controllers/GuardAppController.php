<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardAppController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (! $request->user()->hasGuardRole()) {
            return redirect($request->user()->homePath());
        }

        return view('guard-app');
    }
}
