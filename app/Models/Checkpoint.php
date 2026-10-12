<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\CheckpointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string|null $previous_token
 * @property Carbon|null $token_rotated_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['round_id', 'name', 'instructions', 'position', 'token', 'previous_token', 'token_rotated_at', 'is_active'])]
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
            'token_rotated_at' => 'datetime',
        ];
    }

    public function tokenHash(): string
    {
        return hash('sha256', $this->token);
    }

    /**
     * Matches the current token, or the previous one when the capture happened before it was rotated.
     *
     * @param  Builder<Checkpoint>  $query
     */
    #[Scope]
    protected function matchingSyncToken(Builder $query, string $token, CarbonInterface $capturedAt): void
    {
        $query->where(function (Builder $query) use ($token, $capturedAt): void {
            $query->where('token', $token)
                ->orWhere(function (Builder $query) use ($token, $capturedAt): void {
                    $query->where('previous_token', $token)
                        ->where('token_rotated_at', '>', $capturedAt);
                });
        });
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

    /**
     * @return HasMany<CheckpointSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(CheckpointSubmission::class);
    }
}
