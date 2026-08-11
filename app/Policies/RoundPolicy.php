<?php

namespace App\Policies;

use App\Models\Round;
use App\Models\User;

class RoundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAnyAreaOperations();
    }

    public function view(User $user, Round $round): bool
    {
        return $user->canViewAreaOperations($round->area);
    }

    public function create(User $user): bool
    {
        return $user->canManageAnyArea();
    }

    public function update(User $user, Round $round): bool
    {
        return $user->canManageArea($round->area);
    }

    public function delete(User $user, Round $round): bool
    {
        return $user->canManageArea($round->area);
    }
}
