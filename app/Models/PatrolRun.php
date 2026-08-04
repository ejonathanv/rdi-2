<?php

namespace App\Models;

use App\Enums\PatrolRunStatus;
use Database\Factories\PatrolRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $round_id
 * @property PatrolRunStatus $status
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'round_id', 'status', 'started_at', 'finished_at'])]
class PatrolRun extends Model
{
    /** @use HasFactory<PatrolRunFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PatrolRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
     * @return BelongsTo<Round, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * @return HasMany<PatrolCheckpointVisit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(PatrolCheckpointVisit::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === PatrolRunStatus::InProgress;
    }

    public function durationInSeconds(): ?int
    {
        if ($this->finished_at === null) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }
}
