<?php

namespace App\Models;

use Database\Factories\CheckpointSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $checkpoint_id
 * @property int $user_id
 * @property int|null $patrol_run_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['checkpoint_id', 'user_id', 'patrol_run_id'])]
class CheckpointSubmission extends Model
{
    /** @use HasFactory<CheckpointSubmissionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Checkpoint, $this>
     */
    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
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

    /**
     * @return HasMany<CheckpointSubmissionAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(CheckpointSubmissionAnswer::class);
    }
}
