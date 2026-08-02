<?php

namespace App\Models;

use Database\Factories\CheckpointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $round_id
 * @property string $name
 * @property string|null $instructions
 * @property int $position
 * @property string $token
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['round_id', 'name', 'instructions', 'position', 'token', 'is_active'])]
class Checkpoint extends Model
{
    /** @use HasFactory<CheckpointFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Checkpoint $checkpoint): void {
            if (blank($checkpoint->token)) {
                $checkpoint->token = (string) Str::uuid();
            }
        });
    }

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
     * @return BelongsTo<Round, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * @return HasMany<CheckpointQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(CheckpointQuestion::class)->orderBy('position');
    }
}
