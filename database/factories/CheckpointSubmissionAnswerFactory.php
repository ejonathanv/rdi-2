<?php

namespace Database\Factories;

use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use App\Models\CheckpointSubmission;
use App\Models\CheckpointSubmissionAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckpointSubmissionAnswer>
 */
class CheckpointSubmissionAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checkpoint_submission_id' => CheckpointSubmission::factory(),
            'checkpoint_question_id' => CheckpointQuestion::factory(),
            'checkpoint_question_option_id' => CheckpointQuestionOption::factory(),
        ];
    }
}
