<?php

namespace App\Models;

use Database\Factories\CheckpointQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $checkpoint_id
 * @property string $body
 * @property int $position
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['checkpoint_id', 'body', 'position', 'is_active'])]
class CheckpointQuestion extends Model
{
    /** @use HasFactory<CheckpointQuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Checkpoint, $this>
     */
    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    /**
     * @return HasMany<CheckpointQuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(CheckpointQuestionOption::class)->orderBy('position');
    }
}
