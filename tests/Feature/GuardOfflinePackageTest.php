<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use App\Services\CheckpointTokenRotator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardOfflinePackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_downloads_rounds_checkpoints_questions_and_contacts_of_own_areas(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $contact = User::factory()->create(['phone' => '5511112222']);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id, 'position' => 1]);
        Checkpoint::factory()->create(['round_id' => $round->id, 'is_active' => false]);
        $question = CheckpointQuestion::factory()->create(['checkpoint_id' => $checkpoint->id, 'is_active' => true]);
        CheckpointQuestionOption::factory()->create(['checkpoint_question_id' => $question->id]);
        Round::factory()->create(['area_id' => $area->id, 'is_active' => false]);

        $otherRound = Round::factory()->create();

        $response = $this->actingAs($guard)
            ->getJson(route('guard.offline.package'))
            ->assertOk()
            ->assertJsonPath('user.id', $guard->id)
            ->assertJsonCount(1, 'rounds')
            ->assertJsonPath('rounds.0.id', $round->id)
            ->assertJsonCount(1, 'rounds.0.checkpoints')
            ->assertJsonPath('rounds.0.checkpoints.0.token_hash', hash('sha256', $checkpoint->token))
            ->assertJsonMissingPath('rounds.0.checkpoints.0.token')
            ->assertJsonPath('rounds.0.checkpoints.0.questions.0.id', $question->id)
            ->assertJsonCount(1, 'rounds.0.checkpoints.0.questions.0.options')
            ->assertJsonPath('areas.0.emergency_contacts.0.phone', '5511112222')
            ->assertJsonPath('active_patrol', null);

        $this->assertNotContains($otherRound->id, collect($response->json('rounds'))->pluck('id'));
        $this->assertIsString($response->json('version'));
    }

    public function test_version_changes_when_rounds_change(): void
    {
        [$guard, $round] = $this->guardWithRound();

        $first = $this->actingAs($guard)->getJson(route('guard.offline.package'))->json('version');
        $round->update(['title' => 'Nuevo título']);
        $second = $this->actingAs($guard)->getJson(route('guard.offline.package'))->json('version');

        $this->assertNotSame($first, $second);
    }

    public function test_version_changes_when_qr_tokens_are_regenerated(): void
    {
        [$guard, $round] = $this->guardWithRound();
        Checkpoint::factory()->create(['round_id' => $round->id]);

        $first = $this->actingAs($guard)->getJson(route('guard.offline.package'))->json('version');
        app(CheckpointTokenRotator::class)->rotate(Checkpoint::query()->where('round_id', $round->id));
        $second = $this->actingAs($guard)->getJson(route('guard.offline.package'))->json('version');

        $this->assertNotSame($first, $second);
    }

    public function test_active_patrol_started_online_receives_client_uuid(): void
    {
        [$guard, $round] = $this->guardWithRound();
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);
        $patrol = PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::InProgress,
            'started_at' => now()->subMinutes(10),
        ]);
        $patrol->visits()->create([
            'checkpoint_id' => $checkpoint->id,
            'reviewed_at' => now(),
            'outcome' => 'all_clear',
        ]);

        $response = $this->actingAs($guard)
            ->getJson(route('guard.offline.package'))
            ->assertOk()
            ->assertJsonPath('active_patrol.round_id', $round->id)
            ->assertJsonPath('active_patrol.visited_checkpoint_ids', [$checkpoint->id]);

        $this->assertSame($patrol->fresh()->client_uuid, $response->json('active_patrol.client_uuid'));
        $this->assertNotNull($patrol->fresh()->client_uuid);
    }

    public function test_non_guard_cannot_download_package(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('guard.offline.package'))->assertForbidden();
    }

    public function test_guest_cannot_download_package(): void
    {
        $this->getJson(route('guard.offline.package'))->assertUnauthorized();
    }

    /**
     * @return array{0: User, 1: Round}
     */
    private function guardWithRound(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $round = Round::factory()->create(['area_id' => $area->id]);

        return [$guard, $round];
    }
}
