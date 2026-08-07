<?php

namespace App\Policies;

use App\Models\IncidentCategory;
use App\Models\User;

class IncidentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageAnyArea();
    }

    public function view(User $user, IncidentCategory $incidentCategory): bool
    {
        return $user->canManageArea($incidentCategory->area);
    }

    public function create(User $user): bool
    {
        return $user->canManageAnyArea();
    }

    public function update(User $user, IncidentCategory $incidentCategory): bool
    {
        return $user->canManageArea($incidentCategory->area);
    }

    public function delete(User $user, IncidentCategory $incidentCategory): bool
    {
        return $user->canManageArea($incidentCategory->area);
    }
}
