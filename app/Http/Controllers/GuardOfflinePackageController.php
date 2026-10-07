<?php

namespace App\Http\Controllers;

use App\Services\GuardOfflinePackageBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardOfflinePackageController extends Controller
{
    public function __invoke(Request $request, GuardOfflinePackageBuilder $builder): JsonResponse
    {
        abort_unless($request->user()->hasGuardRole(), 403);

        return response()->json([
            ...$builder->build($request->user()),
            'csrf_token' => $request->session()->token(),
        ]);
    }
}
