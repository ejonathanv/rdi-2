<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckpointTokenRotationTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->area = Area::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->areas()->attach($this->area->id, ['role' => AreaRole::Admin->value]);
    }

    public function test_admin_regenerates_tokens_of_a_round_keeping_previous_ones(): void
    {
        $round = Round::factory()->create(['area_id' => $this->area->id]);
        $checkpoints = Checkpoint::factory()->count(2)->create(['round_id' => $round->id]);
        $otherRoundCheckpoint = Checkpoint::factory()->create([
            'round_id' => Round::factory()->create(['area_id' => $this->area->id])->id,
        ]);

        $this->actingAs($this->admin)
            ->from(route('rounds.edit', $round))
            ->post(route('rounds.tokens.regenerate', $round))
            ->assertRedirect(route('rounds.edit', $round));

        foreach ($checkpoints as $checkpoint) {
            $fresh = $checkpoint->fresh();

            $this->assertNotSame($checkpoint->token, $fresh->token);
            $this->assertSame($checkpoint->token, $fresh->previous_token);
            $this->assertNotNull($fresh->token_rotated_at);
        }

        $this->assertSame($otherRoundCheckpoint->token, $otherRoundCheckpoint->fresh()->token);
        $this->assertNull($otherRoundCheckpoint->fresh()->previous_token);
    }

    public function test_admin_regenerates_all_tokens_of_current_area_only(): void
    {
        $first = Checkpoint::factory()->create([
            'round_id' => Round::factory()->create(['area_id' => $this->area->id])->id,
        ]);
        $second = Checkpoint::factory()->create([
            'round_id' => Round::factory()->create(['area_id' => $this->area->id])->id,
        ]);
        $foreign = Checkpoint::factory()->create();

        $this->actingAs($this->admin)
            ->withSession(['current_area_id' => $this->area->id])
            ->from(route('rounds.index'))
            ->post(route('rounds.tokens.regenerate-all'))
            ->assertRedirect(route('rounds.index'));

        $this->assertNotSame($first->token, $first->fresh()->token);
        $this->assertNotSame($second->token, $second->fresh()->token);
        $this->assertSame($foreign->token, $foreign->fresh()->token);
    }

    public function test_guard_and_admin_of_other_area_cannot_regenerate(): void
    {
        $round = Round::factory()->create(['area_id' => $this->area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        $guard = User::factory()->create();
        $guard->areas()->attach($this->area->id, ['role' => AreaRole::Guard->value]);

        $otherArea = Area::factory()->create();
        $otherAdmin = User::factory()->create();
        $otherAdmin->areas()->attach($otherArea->id, ['role' => AreaRole::Admin->value]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $this->area->id])
            ->post(route('rounds.tokens.regenerate', $round))
            ->assertForbidden();

        $this->actingAs($otherAdmin)
            ->post(route('rounds.tokens.regenerate', $round))
            ->assertForbidden();

        $this->actingAs($otherAdmin)
            ->withSession(['current_area_id' => $this->area->id])
            ->post(route('rounds.tokens.regenerate-all'));

        $this->assertSame($checkpoint->token, $checkpoint->fresh()->token);
    }

    public function test_print_sheet_lists_only_active_checkpoints_with_scan_urls(): void
    {
        $round = Round::factory()->create(['area_id' => $this->area->id]);
        $active = Checkpoint::factory()->create(['round_id' => $round->id, 'position' => 1]);
        Checkpoint::factory()->create(['round_id' => $round->id, 'position' => 2, 'is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('rounds.qr.print', $round))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('rounds/print-qr')
                ->has('checkpoints', 1)
                ->where('checkpoints.0.id', $active->id)
                ->where('checkpoints.0.scan_url', route('checkpoints.scan.show', $active->token))
                ->where('tokensRotatedAt', null));
    }

    public function test_old_token_stops_working_online_after_regeneration(): void
    {
        $round = Round::factory()->create(['area_id' => $this->area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);
        $guard = User::factory()->create();
        $guard->areas()->attach($this->area->id, ['role' => AreaRole::Guard->value]);
        $patrol = PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::InProgress,
        ]);
        $oldToken = $checkpoint->token;

        $this->actingAs($this->admin)->post(route('rounds.tokens.regenerate', $round));

        $this->actingAs($guard)
            ->get(route('checkpoints.scan.show', $oldToken))
            ->assertNotFound();

        $this->actingAs($guard)
            ->from(route('guard.patrols.show', $patrol))
            ->post(route('guard.patrols.verify-checkpoint', [$patrol, $checkpoint]), ['token' => $oldToken])
            ->assertSessionHasErrors('token');
    }

    public function test_guard_patrol_page_exposes_token_hash_instead_of_token(): void
    {
        $round = Round::factory()->create(['area_id' => $this->area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);
        $guard = User::factory()->create();
        $guard->areas()->attach($this->area->id, ['role' => AreaRole::Guard->value]);
        $patrol = PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::InProgress,
        ]);

        $this->actingAs($guard)
            ->get(route('guard.patrols.show', $patrol))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('patrol.checkpoints.0.token_hash', hash('sha256', $checkpoint->token))
                ->missing('patrol.checkpoints.0.token'));
    }
}
