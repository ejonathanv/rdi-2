<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use App\Models\Incident;
use App\Models\PanicAlert;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use App\Services\CheckpointTokenRotator;
use App\Services\IncidentAiProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuardOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $guard;

    private Area $area;

    private Round $round;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->mock(IncidentAiProcessor::class, function ($mock): void {
            $mock->shouldReceive('process')->andReturn([
                'cleaned_message' => 'Mensaje limpio',
                'category_code' => null,
                'new_category' => null,
            ]);
        });

        $this->area = Area::factory()->create();
        $this->guard = User::factory()->create();
        $this->guard->areas()->attach($this->area->id, ['role' => AreaRole::Guard->value]);
        $this->round = Round::factory()->create(['area_id' => $this->area->id]);
    }

    public function test_patrol_sync_uses_client_start_time_and_is_idempotent(): void
    {
        $uuid = (string) Str::uuid();
        $startedAt = now()->subHours(2)->startOfMinute();
        $payload = [
            'client_uuid' => $uuid,
            'round_id' => $this->round->id,
            'started_at' => $startedAt->toIso8601String(),
        ];

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.patrols'), $payload)
            ->assertCreated()
            ->assertJsonPath('duplicate', false);

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.patrols'), $payload)
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, PatrolRun::query()->count());
        $patrol = PatrolRun::query()->firstOrFail();
        $this->assertSame($uuid, $patrol->client_uuid);
        $this->assertTrue($patrol->started_at->equalTo($startedAt));
        $this->assertSame(PatrolRunStatus::InProgress, $patrol->status);
    }

    public function test_patrol_sync_rejects_round_of_other_area(): void
    {
        $otherRound = Round::factory()->create();

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.patrols'), [
                'client_uuid' => (string) Str::uuid(),
                'round_id' => $otherRound->id,
                'started_at' => now()->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('round_id');
    }

    public function test_client_timestamps_in_future_or_too_old_are_rejected(): void
    {
        foreach ([now()->addHour(), now()->subDays(30)] as $startedAt) {
            $this->actingAs($this->guard)
                ->postJson(route('guard.sync.patrols'), [
                    'client_uuid' => (string) Str::uuid(),
                    'round_id' => $this->round->id,
                    'started_at' => $startedAt->toIso8601String(),
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('started_at');
        }

        $this->assertSame(0, PatrolRun::query()->count());
    }

    public function test_visit_sync_records_answers_photos_and_completes_patrol_with_client_time(): void
    {
        $checkpoint = Checkpoint::factory()->create(['round_id' => $this->round->id]);
        $question = CheckpointQuestion::factory()->create(['checkpoint_id' => $checkpoint->id, 'is_active' => true]);
        $option = CheckpointQuestionOption::factory()->create(['checkpoint_question_id' => $question->id]);
        $patrol = $this->offlinePatrol();
        $reviewedAt = now()->subMinutes(30)->startOfMinute();
        $uuid = (string) Str::uuid();

        $payload = [
            'client_uuid' => $uuid,
            'patrol_client_uuid' => $patrol->client_uuid,
            'checkpoint_token' => $checkpoint->token,
            'outcome' => PatrolVisitOutcome::Questionnaire->value,
            'answers' => [$question->id => $option->id],
            'photos' => [UploadedFile::fake()->image('evidencia.jpg')],
            'reviewed_at' => $reviewedAt->toIso8601String(),
        ];

        $this->actingAs($this->guard)
            ->post(route('guard.sync.visits'), $payload, ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('patrol_status', PatrolRunStatus::Completed->value);

        $this->actingAs($this->guard)
            ->post(route('guard.sync.visits'), $payload, ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $visit = PatrolCheckpointVisit::query()->sole();
        $this->assertSame($uuid, $visit->client_uuid);
        $this->assertTrue($visit->reviewed_at->equalTo($reviewedAt));
        $this->assertSame(1, $visit->photos()->count());
        $this->assertSame($option->id, $visit->submission->answers()->sole()->checkpoint_question_option_id);

        $patrol->refresh();
        $this->assertSame(PatrolRunStatus::Completed, $patrol->status);
        $this->assertTrue($patrol->finished_at->equalTo($reviewedAt));
    }

    public function test_visit_sync_returns_conflict_when_patrol_not_synced_yet(): void
    {
        $checkpoint = Checkpoint::factory()->create(['round_id' => $this->round->id]);

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.visits'), [
                'client_uuid' => (string) Str::uuid(),
                'patrol_client_uuid' => (string) Str::uuid(),
                'checkpoint_token' => $checkpoint->token,
                'outcome' => PatrolVisitOutcome::AllClear->value,
                'reviewed_at' => now()->toIso8601String(),
            ])
            ->assertStatus(409);
    }

    public function test_visit_sync_rejects_checkpoint_of_other_round_and_already_visited(): void
    {
        $patrol = $this->offlinePatrol();
        $checkpoint = Checkpoint::factory()->create(['round_id' => $this->round->id]);
        Checkpoint::factory()->create(['round_id' => $this->round->id]);
        $foreignCheckpoint = Checkpoint::factory()->create();

        $payload = fn (string $token) => [
            'client_uuid' => (string) Str::uuid(),
            'patrol_client_uuid' => $patrol->client_uuid,
            'checkpoint_token' => $token,
            'outcome' => PatrolVisitOutcome::AllClear->value,
            'reviewed_at' => now()->toIso8601String(),
        ];

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.visits'), $payload($foreignCheckpoint->token))
            ->assertJsonValidationErrors('checkpoint_token');

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.visits'), $payload($checkpoint->token))
            ->assertCreated();

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.visits'), $payload($checkpoint->token))
            ->assertJsonValidationErrors('checkpoint_token');

        $this->assertSame(1, PatrolCheckpointVisit::query()->count());
    }

    public function test_visit_sync_accepts_previous_token_only_when_captured_before_rotation(): void
    {
        $patrol = $this->offlinePatrol();
        $checkpoint = Checkpoint::factory()->create(['round_id' => $this->round->id]);
        Checkpoint::factory()->create(['round_id' => $this->round->id]);
        $oldToken = $checkpoint->token;

        $this->travelTo(now()->subMinutes(10));
        $capturedBeforeRotation = now()->toIso8601String();
        $this->travelBack();

        app(CheckpointTokenRotator::class)->rotate(Checkpoint::query()->whereKey($checkpoint->id));

        $payload = fn (string $reviewedAt) => [
            'client_uuid' => (string) Str::uuid(),
            'patrol_client_uuid' => $patrol->client_uuid,
            'checkpoint_token' => $oldToken,
            'outcome' => PatrolVisitOutcome::AllClear->value,
            'reviewed_at' => $reviewedAt,
        ];

        $this->travel(1)->minutes();

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.visits'), $payload(now()->toIso8601String()))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('checkpoint_token');

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.visits'), $payload($capturedBeforeRotation))
            ->assertCreated();

        $this->assertDatabaseHas('patrol_checkpoint_visits', [
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
        ]);
    }

    public function test_visit_sync_accepts_checkpoint_deactivated_after_download(): void
    {
        $patrol = $this->offlinePatrol();
        $checkpoint = Checkpoint::factory()->create(['round_id' => $this->round->id, 'is_active' => false]);

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.visits'), [
                'client_uuid' => (string) Str::uuid(),
                'patrol_client_uuid' => $patrol->client_uuid,
                'checkpoint_token' => $checkpoint->token,
                'outcome' => PatrolVisitOutcome::AllClear->value,
                'reviewed_at' => now()->toIso8601String(),
            ])
            ->assertCreated();
    }

    public function test_guard_cannot_sync_visits_to_another_guards_patrol(): void
    {
        $patrol = $this->offlinePatrol();
        $checkpoint = Checkpoint::factory()->create(['round_id' => $this->round->id]);
        $otherGuard = User::factory()->create();
        $otherGuard->areas()->attach($this->area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($otherGuard)
            ->postJson(route('guard.sync.visits'), [
                'client_uuid' => (string) Str::uuid(),
                'patrol_client_uuid' => $patrol->client_uuid,
                'checkpoint_token' => $checkpoint->token,
                'outcome' => PatrolVisitOutcome::AllClear->value,
                'reviewed_at' => now()->toIso8601String(),
            ])
            ->assertStatus(409);
    }

    public function test_incident_sync_is_idempotent_and_keeps_occurred_at(): void
    {
        $occurredAt = now()->subHour()->startOfMinute();
        $payload = [
            'client_uuid' => (string) Str::uuid(),
            'area_id' => $this->area->id,
            'message' => 'Puerta forzada en bodega',
            'is_urgent' => true,
            'photos' => [UploadedFile::fake()->image('puerta.jpg')],
            'occurred_at' => $occurredAt->toIso8601String(),
        ];

        $this->actingAs($this->guard)
            ->post(route('guard.sync.incidents'), $payload, ['Accept' => 'application/json'])
            ->assertCreated();

        $this->actingAs($this->guard)
            ->post(route('guard.sync.incidents'), $payload, ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $incident = Incident::query()->sole();
        $this->assertTrue($incident->occurred_at->equalTo($occurredAt));
        $this->assertTrue($incident->is_urgent);
        $this->assertSame('Mensaje limpio', $incident->message_cleaned);
        $this->assertSame(1, $incident->photos()->count());
    }

    public function test_incident_sync_from_checkpoint_records_visit(): void
    {
        $patrol = $this->offlinePatrol();
        $checkpoint = Checkpoint::factory()->create(['round_id' => $this->round->id]);
        Checkpoint::factory()->create(['round_id' => $this->round->id]);
        $visitUuid = (string) Str::uuid();

        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.incidents'), [
                'client_uuid' => (string) Str::uuid(),
                'visit_client_uuid' => $visitUuid,
                'message' => 'Fuga de agua',
                'checkpoint_token' => $checkpoint->token,
                'patrol_client_uuid' => $patrol->client_uuid,
                'occurred_at' => now()->subMinutes(5)->toIso8601String(),
            ])
            ->assertCreated();

        $visit = PatrolCheckpointVisit::query()->sole();
        $this->assertSame(PatrolVisitOutcome::Incident, $visit->outcome);
        $this->assertSame($visitUuid, $visit->client_uuid);
        $this->assertSame($checkpoint->id, Incident::query()->sole()->checkpoint_id);
    }

    public function test_incident_sync_rejects_foreign_area(): void
    {
        $this->actingAs($this->guard)
            ->postJson(route('guard.sync.incidents'), [
                'client_uuid' => (string) Str::uuid(),
                'area_id' => Area::factory()->create()->id,
                'message' => 'Algo',
                'occurred_at' => now()->toIso8601String(),
            ])
            ->assertJsonValidationErrors('area_id');
    }

    public function test_panic_sync_is_idempotent_and_marks_offline_time(): void
    {
        $occurredAt = now()->subMinutes(40)->startOfMinute();
        $payload = [
            'client_uuid' => (string) Str::uuid(),
            'area_id' => $this->area->id,
            'occurred_at' => $occurredAt->toIso8601String(),
        ];

        $this->actingAs($this->guard)->postJson(route('guard.sync.panic'), $payload)->assertCreated();
        $this->actingAs($this->guard)->postJson(route('guard.sync.panic'), $payload)->assertOk();

        $alert = PanicAlert::query()->sole();
        $this->assertTrue($alert->occurred_at->equalTo($occurredAt));
        $this->assertTrue($alert->wasRecordedOffline());
    }

    public function test_non_guard_cannot_sync(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('guard.sync.panic'), [
                'client_uuid' => (string) Str::uuid(),
                'area_id' => $this->area->id,
                'occurred_at' => now()->toIso8601String(),
            ])
            ->assertForbidden();
    }

    private function offlinePatrol(): PatrolRun
    {
        return PatrolRun::factory()->create([
            'client_uuid' => (string) Str::uuid(),
            'user_id' => $this->guard->id,
            'round_id' => $this->round->id,
            'status' => PatrolRunStatus::InProgress,
            'started_at' => now()->subHour(),
        ]);
    }
}
