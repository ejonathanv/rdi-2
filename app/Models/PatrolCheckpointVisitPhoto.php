<?php

namespace App\Models;

use Database\Factories\PatrolCheckpointVisitPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $patrol_checkpoint_visit_id
 * @property string $path
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'patrol_checkpoint_visit_id',
    'path',
    'position',
])]
class PatrolCheckpointVisitPhoto extends Model
{
    /** @use HasFactory<PatrolCheckpointVisitPhotoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<PatrolCheckpointVisit, $this>
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(PatrolCheckpointVisit::class, 'patrol_checkpoint_visit_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
