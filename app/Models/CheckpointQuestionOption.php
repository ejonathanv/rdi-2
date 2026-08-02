<?php

namespace App\Models;

use Database\Factories\CheckpointQuestionOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $checkpoint_question_id
 * @property string $label
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['checkpoint_question_id', 'label', 'position'])]
class CheckpointQuestionOption extends Model
{
    /** @use HasFactory<CheckpointQuestionOptionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CheckpointQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(CheckpointQuestion::class, 'checkpoint_question_id');
    }
}
