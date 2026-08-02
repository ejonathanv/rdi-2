<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuardHomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->hasGuardRole(), 403);

        return Inertia::render('guard/home');
    }
}
