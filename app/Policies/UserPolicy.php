<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageAnyArea();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->canManageAnyArea()) {
            return false;
        }

        $manageableIds = $user->manageableAreaIds();

        return $model->areas()->whereIn('areas.id', $manageableIds)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canManageAnyArea();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($model->isSuperAdmin()) {
            return false;
        }

        return $this->view($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $this->update($user, $model);
    }
}
