<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Log;
use Throwable;

trait SafelyRunsSideEffects
{
    protected function safely(string $context, callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            Log::error("Fallo en {$context}; el flujo de negocio continúa.", [
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
        }
    }
}
