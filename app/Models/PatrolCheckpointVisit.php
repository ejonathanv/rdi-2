<?php

namespace App\Models;

use App\Enums\PatrolVisitOutcome;
use Database\Factories\PatrolCheckpointVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patrol_run_id
 * @property int $checkpoint_id
 * @property Carbon $reviewed_at
 * @property PatrolVisitOutcome $outcome
 * @property int|null $checkpoint_submission_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'patrol_run_id',
    'checkpoint_id',
    'reviewed_at',
    'outcome',
    'checkpoint_submission_id',
])]
class PatrolCheckpointVisit extends Model
{
    /** @use HasFactory<PatrolCheckpointVisitFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'outcome' => PatrolVisitOutcome::class,
        ];
    }

    /**
     * @return BelongsTo<PatrolRun, $this>
     */
    public function patrolRun(): BelongsTo
    {
        return $this->belongsTo(PatrolRun::class);
    }

    /**
     * @return BelongsTo<Checkpoint, $this>
     */
    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    /**
     * @return BelongsTo<CheckpointSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(CheckpointSubmission::class, 'checkpoint_submission_id');
    }

    /**
     * @return HasMany<PatrolCheckpointVisitPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PatrolCheckpointVisitPhoto::class)->orderBy('position');
    }
}
