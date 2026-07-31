<?php

namespace App\Models;

use App\Enums\AreaRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $area_id
 * @property AreaRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'area_id', 'role'])]
class AreaUser extends Pivot
{
    public $incrementing = true;

    protected $table = 'area_user';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => AreaRole::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
