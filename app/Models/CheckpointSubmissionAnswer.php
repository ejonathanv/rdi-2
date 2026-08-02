<?php

namespace App\Models;

use Database\Factories\CheckpointSubmissionAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $checkpoint_submission_id
 * @property int $checkpoint_question_id
 * @property int $checkpoint_question_option_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'checkpoint_submission_id',
    'checkpoint_question_id',
    'checkpoint_question_option_id',
])]
class CheckpointSubmissionAnswer extends Model
{
    /** @use HasFactory<CheckpointSubmissionAnswerFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<CheckpointSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(CheckpointSubmission::class, 'checkpoint_submission_id');
    }

    /**
     * @return BelongsTo<CheckpointQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(CheckpointQuestion::class, 'checkpoint_question_id');
    }

    /**
     * @return BelongsTo<CheckpointQuestionOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(CheckpointQuestionOption::class, 'checkpoint_question_option_id');
    }
}
