<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use App\Models\CheckpointSubmission;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckpointScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $checkpoint = $this->checkpointWithQuestion();

        $this->get(route('checkpoints.scan.show', $checkpoint->token))
            ->assertRedirect(route('login'));
    }

    public function test_guard_of_area_can_view_scan_page(): void
    {
        [$guard, $checkpoint, $question] = $this->guardWithQuestion();

        $this->actingAs($guard)
            ->get(route('checkpoints.scan.show', $checkpoint->token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('checkpoints/scan')
                ->where('checkpoint.token', $checkpoint->token)
                ->has('questions', 1)
                ->where('questions.0.id', $question->id));
    }

    public function test_guard_of_another_area_cannot_view_scan_page(): void
    {
        [, $checkpoint] = $this->guardWithQuestion();

        $otherArea = Area::factory()->create();
        $otherGuard = User::factory()->create();
        $otherGuard->areas()->attach($otherArea->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($otherGuard)
            ->get(route('checkpoints.scan.show', $checkpoint->token))
            ->assertForbidden();
    }

    public function test_contact_cannot_respond_to_checkpoint(): void
    {
        $area = Area::factory()->create();
        $contact = User::factory()->create();
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);
        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        $this->actingAs($contact)
            ->get(route('checkpoints.scan.show', $checkpoint->token))
            ->assertForbidden();
    }

    public function test_guard_can_submit_answers(): void
    {
        [$guard, $checkpoint, $question, $optionYes, $optionNo] = $this->guardWithQuestion();

        $this->actingAs($guard)
            ->post(route('checkpoints.scan.store', $checkpoint->token), [
                'answers' => [
                    $question->id => $optionYes->id,
                ],
            ])
            ->assertRedirect(route('checkpoints.scan.complete', $checkpoint->token));

        $submission = CheckpointSubmission::query()
            ->where('checkpoint_id', $checkpoint->id)
            ->where('user_id', $guard->id)
            ->first();

        $this->assertNotNull($submission);
        $this->assertDatabaseHas('checkpoint_submission_answers', [
            'checkpoint_submission_id' => $submission->id,
            'checkpoint_question_id' => $question->id,
            'checkpoint_question_option_id' => $optionYes->id,
        ]);
        $this->assertDatabaseMissing('checkpoint_submission_answers', [
            'checkpoint_submission_id' => $submission->id,
            'checkpoint_question_option_id' => $optionNo->id,
        ]);
    }

    public function test_invalid_option_is_rejected(): void
    {
        [$guard, $checkpoint, $question] = $this->guardWithQuestion();

        $this->actingAs($guard)
            ->from(route('checkpoints.scan.show', $checkpoint->token))
            ->post(route('checkpoints.scan.store', $checkpoint->token), [
                'answers' => [
                    $question->id => 999999,
                ],
            ])
            ->assertSessionHasErrors("answers.{$question->id}");

        $this->assertDatabaseCount('checkpoint_submissions', 0);
    }

    public function test_missing_answer_is_rejected(): void
    {
        [$guard, $checkpoint] = $this->guardWithQuestion();

        $this->actingAs($guard)
            ->from(route('checkpoints.scan.show', $checkpoint->token))
            ->post(route('checkpoints.scan.store', $checkpoint->token), [
                'answers' => [],
            ])
            ->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('checkpoint_submissions', 0);
    }

    /**
     * @return array{0: User, 1: Checkpoint, 2: CheckpointQuestion, 3: CheckpointQuestionOption, 4: CheckpointQuestionOption}
     */
    private function guardWithQuestion(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $guard->load('areas');

        $checkpoint = $this->checkpointWithQuestion($area);
        $question = $checkpoint->questions()->with('options')->firstOrFail();
        $options = $question->options;

        return [$guard, $checkpoint, $question, $options[0], $options[1]];
    }

    private function checkpointWithQuestion(?Area $area = null): Checkpoint
    {
        $area ??= Area::factory()->create();
        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);
        $checkpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'is_active' => true,
        ]);

        $question = CheckpointQuestion::factory()->create([
            'checkpoint_id' => $checkpoint->id,
            'body' => '¿Está el candado cerrado?',
            'position' => 1,
            'is_active' => true,
        ]);

        $question->options()->createMany([
            ['label' => 'Sí', 'position' => 1],
            ['label' => 'No', 'position' => 2],
        ]);

        return $checkpoint->fresh(['questions.options', 'round.area']);
    }
}
