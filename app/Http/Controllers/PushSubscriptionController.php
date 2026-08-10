<?php

namespace App\Http\Controllers;

use App\Http\Requests\PushSubscriptions\DestroyPushSubscriptionRequest;
use App\Http\Requests\PushSubscriptions\StorePushSubscriptionRequest;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['content_encoding'] ?? null,
        );

        return response()->json(['status' => 'ok']);
    }

    public function destroy(DestroyPushSubscriptionRequest $request): JsonResponse
    {
        $request->user()->deletePushSubscription($request->validated('endpoint'));

        return response()->json(['status' => 'ok']);
    }
}
