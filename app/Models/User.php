<?php

namespace App\Models;

use App\Enums\AreaRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_super_admin
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'is_super_admin'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Area, $this>
     */
    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class)
            ->withPivot('role')
            ->withTimestamps()
            ->using(AreaUser::class);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function roleIn(Area $area): ?AreaRole
    {
        $membership = $this->areas->firstWhere('id', $area->id);

        if (! $membership) {
            return null;
        }

        $role = $membership->pivot->role;

        return $role instanceof AreaRole ? $role : AreaRole::from($role);
    }

    public function hasRoleIn(Area $area, AreaRole $role): bool
    {
        return $this->roleIn($area) === $role;
    }

    public function canManageArea(Area $area): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasRoleIn($area, AreaRole::Admin);
    }

    public function canManageAnyArea(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->areas()
            ->wherePivot('role', AreaRole::Admin->value)
            ->exists();
    }

    public function canAccessArea(Area $area): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->areas()->where('areas.id', $area->id)->exists();
    }

    public function canRespondToCheckpoint(Checkpoint $checkpoint): bool
    {
        $checkpoint->loadMissing('round.area');

        $area = $checkpoint->round->area;

        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->roleIn($area);

        return $role === AreaRole::Guard || $role === AreaRole::Admin;
    }

    /**
     * Area IDs this user can manage (all areas if super-admin).
     *
     * @return list<int>
     */
    public function manageableAreaIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Area::query()->pluck('id')->all();
        }

        return $this->areas()
            ->wherePivot('role', AreaRole::Admin->value)
            ->pluck('areas.id')
            ->all();
    }
}
