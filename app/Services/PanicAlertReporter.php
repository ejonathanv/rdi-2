<?php

namespace App\Services;

use App\Models\Area;
use App\Models\PanicAlert;
use App\Models\PatrolRun;
use App\Models\User;
use Carbon\CarbonInterface;

class PanicAlertReporter
{
    public function __construct(private PanicAlertNotifier $notifier) {}

    public function report(
        User $guard,
        Area $area,
        ?PatrolRun $patrol = null,
        ?CarbonInterface $occurredAt = null,
        ?string $clientUuid = null,
    ): PanicAlert {
        $alert = PanicAlert::query()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'patrol_run_id' => $patrol?->id,
            'client_uuid' => $clientUuid,
            'occurred_at' => $occurredAt ?? now(),
        ]);

        $this->notifier->notify($alert);

        return $alert->fresh(['area', 'user', 'patrolRun.round']);
    }
}
