<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRondinTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_rounds_for_rondines(): void
    {
        [$admin, $area, $round] = $this->adminWithRound();

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('rondines/index')
                ->has('rounds', 1)
                ->where('rounds.0.id', $round->id));
    }

    public function test_admin_can_see_patrol_runs_for_a_round(): void
    {
        [$admin, $area, $round, $guard, $patrol] = $this->adminWithCompletedPatrol();

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.rounds.show', $round))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('rondines/round')
                ->has('patrols', 1)
                ->where('patrols.0.id', $patrol->id)
                ->where('patrols.0.guard.name', $guard->name)
                ->where('patrols.0.status', PatrolRunStatus::Completed->value));
    }

    public function test_admin_can_see_patrol_detail_and_download_pdf(): void
    {
        [$admin, $area, $round, $guard, $patrol] = $this->adminWithCompletedPatrol();

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.patrols.show', [$round, $patrol]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('rondines/patrol')
                ->where('patrol.id', $patrol->id)
                ->where('patrol.guard.name', $guard->name)
                ->has('checkpoints', 1)
                ->where('checkpoints.0.visited', true)
                ->where('checkpoints.0.outcome', PatrolVisitOutcome::AllClear->value));

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.patrols.pdf', [$round, $patrol]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_guard_cannot_access_rondines(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_view_patrol_from_another_round(): void
    {
        [$admin, $area, $round, $guard, $patrol] = $this->adminWithCompletedPatrol();
        $otherRound = Round::factory()->create(['area_id' => $area->id]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.patrols.show', [$otherRound, $patrol]))
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Area, 2: Round}
     */
    private function adminWithRound(): array
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);

        return [$admin, $area, $round];
    }

    /**
     * @return array{0: User, 1: Area, 2: Round, 3: User, 4: PatrolRun}
     */
    private function adminWithCompletedPatrol(): array
    {
        [$admin, $area, $round] = $this->adminWithRound();

        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $checkpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'is_active' => true,
            'position' => 1,
        ]);

        $patrol = PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::Completed,
            'started_at' => now()->subHour(),
            'finished_at' => now(),
        ]);

        PatrolCheckpointVisit::factory()->create([
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'outcome' => PatrolVisitOutcome::AllClear,
            'reviewed_at' => now()->subMinutes(30),
        ]);

        return [$admin, $area, $round, $guard, $patrol];
    }
}
