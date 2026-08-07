<?php

namespace App\Models;

use App\Enums\IncidentStatus;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $area_id
 * @property int $user_id
 * @property int|null $patrol_run_id
 * @property int|null $checkpoint_id
 * @property string $message_raw
 * @property string|null $message_cleaned
 * @property int|null $incident_category_id
 * @property bool $is_urgent
 * @property IncidentStatus $status
 * @property int|null $assigned_to_id
 * @property Carbon|null $acknowledged_at
 * @property int|null $resolved_by_id
 * @property Carbon|null $resolved_at
 * @property string|null $resolution_notes
 * @property Carbon|null $categorized_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'area_id',
    'user_id',
    'patrol_run_id',
    'checkpoint_id',
    'message_raw',
    'message_cleaned',
    'incident_category_id',
    'is_urgent',
    'status',
    'assigned_to_id',
    'acknowledged_at',
    'resolved_by_id',
    'resolved_at',
    'resolution_notes',
    'categorized_at',
])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'status' => IncidentStatus::class,
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'categorized_at' => 'datetime',
        ];
    }

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
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
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
     * @return BelongsTo<IncidentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'incident_category_id');
    }

    /**
     * @return HasMany<IncidentPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(IncidentPhoto::class)->orderBy('position');
    }

    public function responseSeconds(): ?int
    {
        if ($this->acknowledged_at === null || $this->created_at === null) {
            return null;
        }

        return max(0, (int) $this->created_at->diffInSeconds($this->acknowledged_at));
    }

    public function resolutionSeconds(): ?int
    {
        if ($this->resolved_at === null || $this->created_at === null) {
            return null;
        }

        return max(0, (int) $this->created_at->diffInSeconds($this->resolved_at));
    }
}
