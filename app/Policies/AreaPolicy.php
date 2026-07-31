<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->areas()->exists();
    }

    public function view(User $user, Area $area): bool
    {
        return $user->canAccessArea($area);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Area $area): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Area $area): bool
    {
        return $user->isSuperAdmin();
    }
}
