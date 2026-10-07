<?php

namespace App\Concerns;

use App\Models\PatrolRun;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

trait OfflineSyncRules
{
    /**
     * @return list<string>
     */
    protected function clientTimestampRules(): array
    {
        $earliest = now()->subDays((int) config('offline.max_age_days'));
        $latest = now()->addMinutes((int) config('offline.clock_skew_minutes'));

        return [
            'required',
            'date',
            'after_or_equal:'.$earliest->toIso8601String(),
            'before_or_equal:'.$latest->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function clientTimestampMessages(string $field): array
    {
        return [
            "{$field}.after_or_equal" => __('El registro es demasiado antiguo para sincronizarse.'),
            "{$field}.before_or_equal" => __('La hora del dispositivo no es válida. Revisa la fecha y hora del teléfono.'),
        ];
    }

    protected function clientTimestamp(string $field): CarbonInterface
    {
        return CarbonImmutable::parse((string) $this->input($field))->setTimezone(config('app.timezone'));
    }

    /**
     * Resolve the guard's patrol by its client UUID. Responds 409 when it has
     * not been synced yet so the client retries after uploading the patrol.
     */
    protected function ownPatrolOrConflict(string $clientUuid): PatrolRun
    {
        $patrol = PatrolRun::query()
            ->where('client_uuid', $clientUuid)
            ->where('user_id', $this->user()->id)
            ->first();

        if (! $patrol) {
            throw new HttpResponseException(response()->json([
                'message' => __('El recorrido aún no se ha sincronizado.'),
            ], 409));
        }

        return $patrol;
    }

    /**
     * @param  list<int>  $areaIds
     */
    protected function guardCanUseArea(int $areaId, array $areaIds): bool
    {
        return $this->user()->isSuperAdmin() || in_array($areaId, $areaIds, true);
    }
}
