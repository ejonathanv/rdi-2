<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAnyAreaOperations();
    }

    public function view(User $user, Incident $incident): bool
    {
        return $user->canViewAreaOperations($incident->area);
    }

    public function update(User $user, Incident $incident): bool
    {
        return $user->canViewAreaOperations($incident->area);
    }
}
