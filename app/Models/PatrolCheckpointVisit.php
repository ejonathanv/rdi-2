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
 * @property bool $is_urgent
 * @property string|null $urgent_notes
 * @property Carbon|null $urgent_resolved_at
 * @property int|null $urgent_resolved_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'patrol_run_id',
    'checkpoint_id',
    'reviewed_at',
    'outcome',
    'is_urgent',
    'urgent_notes',
    'urgent_resolved_at',
    'urgent_resolved_by_id',
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
            'is_urgent' => 'boolean',
            'urgent_resolved_at' => 'datetime',
        ];
    }

    public function isUrgentOpen(): bool
    {
        return $this->is_urgent && $this->urgent_resolved_at === null;
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
     * @return BelongsTo<User, $this>
     */
    public function urgentResolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'urgent_resolved_by_id');
    }

    /**
     * @return HasMany<PatrolCheckpointVisitPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(PatrolCheckpointVisitPhoto::class)->orderBy('position');
    }
}
