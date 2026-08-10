<?php

namespace App\Services;

use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Collection;

class AreaNotificationRecipients
{
    /**
     * @return Collection<int, User>
     */
    public function forArea(Area|int $area, ?User $except = null): Collection
    {
        $areaId = $area instanceof Area ? $area->id : $area;

        $query = User::query()
            ->whereHas(
                'areas',
                fn ($builder) => $builder->where('areas.id', $areaId),
            );

        if ($except !== null) {
            $query->whereKeyNot($except->id);
        }

        return $query->get();
    }
}
