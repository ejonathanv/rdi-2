<?php

namespace App\Models;

use Database\Factories\PanicAlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $area_id
 * @property int $user_id
 * @property int|null $patrol_run_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['area_id', 'user_id', 'patrol_run_id'])]
class PanicAlert extends Model
{
    /** @use HasFactory<PanicAlertFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PatrolRun, $this>
     */
    public function patrolRun(): BelongsTo
    {
        return $this->belongsTo(PatrolRun::class);
    }
}
