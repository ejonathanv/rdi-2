<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardPatrolTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_can_start_patrol_with_started_at(): void
    {
        [$guard, $round] = $this->guardWithRound();

        $this->actingAs($guard)
            ->post(route('guard.rounds.start', $round))
            ->assertRedirect();

        $patrol = PatrolRun::query()->where('user_id', $guard->id)->first();

        $this->assertNotNull($patrol);
        $this->assertSame(PatrolRunStatus::InProgress, $patrol->status);
        $this->assertNotNull($patrol->started_at);
        $this->assertNull($patrol->finished_at);
    }

    public function test_starting_another_round_while_in_progress_is_blocked(): void
    {
        [$guard, $round] = $this->guardWithRound();
        $otherRound = Round::factory()->create([
            'area_id' => $round->area_id,
            'is_active' => true,
        ]);

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));

        $this->actingAs($guard)
            ->from(route('guard.rounds.index'))
            ->post(route('guard.rounds.start', $otherRound))
            ->assertSessionHasErrors('round');
    }

    public function test_verify_checkpoint_rejects_wrong_token(): void
    {
        [$guard, $round, $checkpoint] = $this->guardWithCheckpoint();

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->from(route('guard.patrols.show', $patrol))
            ->post(route('guard.patrols.verify-checkpoint', [$patrol, $checkpoint]), [
                'token' => 'token-incorrecto',
            ])
            ->assertSessionHasErrors('token');
    }

    public function test_verify_checkpoint_accepts_scan_url_and_opens_scan(): void
    {
        [$guard, $round, $checkpoint] = $this->guardWithCheckpoint();

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->post(route('guard.patrols.verify-checkpoint', [$patrol, $checkpoint]), [
                'token' => url('/scan/'.$checkpoint->token),
            ])
            ->assertRedirect(route('checkpoints.scan.show', $checkpoint->token));
    }

    public function test_questionnaire_creates_visit_and_completes_single_checkpoint_patrol(): void
    {
        [$guard, $round, $checkpoint, $question, $option] = $this->guardWithQuestionCheckpoint();

        $this->actingAs($guard)
            ->post(route('guard.rounds.start', $round))
            ->assertRedirect();

        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession(['active_patrol_run_id' => $patrol->id])
            ->post(route('checkpoints.scan.store', $checkpoint->token), [
                'answers' => [$question->id => $option->id],
            ])
            ->assertRedirect(route('checkpoints.scan.complete', $checkpoint->token));

        $patrol->refresh();

        $this->assertDatabaseHas('patrol_checkpoint_visits', [
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'outcome' => PatrolVisitOutcome::Questionnaire->value,
        ]);
        $this->assertSame(PatrolRunStatus::Completed, $patrol->status);
        $this->assertNotNull($patrol->finished_at);
    }

    public function test_all_clear_marks_visit_without_questions(): void
    {
        [$guard, $round, $checkpoint] = $this->guardWithCheckpoint();

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession(['active_patrol_run_id' => $patrol->id])
            ->post(route('checkpoints.scan.all-clear', $checkpoint->token))
            ->assertRedirect(route('checkpoints.scan.complete', $checkpoint->token));

        $this->assertDatabaseHas('patrol_checkpoint_visits', [
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'outcome' => PatrolVisitOutcome::AllClear->value,
        ]);
    }

    public function test_cannot_all_clear_when_questions_exist(): void
    {
        [$guard, $round, $checkpoint] = $this->guardWithQuestionCheckpoint();

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession(['active_patrol_run_id' => $patrol->id])
            ->from(route('checkpoints.scan.show', $checkpoint->token))
            ->post(route('checkpoints.scan.all-clear', $checkpoint->token))
            ->assertSessionHasErrors('checkpoint');
    }

    /**
     * @return array{0: User, 1: Round}
     */
    private function guardWithRound(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);

        return [$guard, $round];
    }

    /**
     * @return array{0: User, 1: Round, 2: Checkpoint}
     */
    private function guardWithCheckpoint(): array
    {
        [$guard, $round] = $this->guardWithRound();
        $checkpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'is_active' => true,
        ]);

        return [$guard, $round, $checkpoint];
    }

    /**
     * @return array{0: User, 1: Round, 2: Checkpoint, 3: CheckpointQuestion, 4: CheckpointQuestionOption}
     */
    private function guardWithQuestionCheckpoint(): array
    {
        [$guard, $round, $checkpoint] = $this->guardWithCheckpoint();

        $question = CheckpointQuestion::factory()->create([
            'checkpoint_id' => $checkpoint->id,
            'is_active' => true,
            'position' => 1,
        ]);
        $option = $question->options()->create([
            'label' => 'Sí',
            'position' => 1,
        ]);
        $question->options()->create([
            'label' => 'No',
            'position' => 2,
        ]);

        return [$guard, $round, $checkpoint, $question, $option];
    }
}
