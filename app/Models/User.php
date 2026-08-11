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
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_super_admin
 * @property string|null $phone
 * @property bool $notify_via_whatsapp
 * @property bool $notify_via_sms
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'is_super_admin', 'phone', 'notify_via_whatsapp', 'notify_via_sms'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'notify_via_whatsapp' => 'boolean',
            'notify_via_sms' => 'boolean',
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

    public function canViewAreaOperations(Area $area): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->roleIn($area);

        return $role === AreaRole::Admin || $role === AreaRole::Contact;
    }

    public function canViewAnyAreaOperations(): bool
    {
        if ($this->isSuperAdmin() || $this->canManageAnyArea()) {
            return true;
        }

        return $this->areas()
            ->wherePivot('role', AreaRole::Contact->value)
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

    public function isGuardOnly(): bool
    {
        if ($this->isSuperAdmin() || $this->canManageAnyArea()) {
            return false;
        }

        return $this->hasGuardRole();
    }

    public function hasGuardRole(): bool
    {
        return $this->areas()
            ->wherePivot('role', AreaRole::Guard->value)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function guardAreaIds(): array
    {
        return $this->areas()
            ->wherePivot('role', AreaRole::Guard->value)
            ->pluck('areas.id')
            ->all();
    }

    public function homePath(): string
    {
        if ($this->isSuperAdmin() || $this->canManageAnyArea()) {
            return route('dashboard', absolute: false);
        }

        if ($this->hasGuardRole()) {
            return route('guard.home', absolute: false);
        }

        return route('dashboard', absolute: false);
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
