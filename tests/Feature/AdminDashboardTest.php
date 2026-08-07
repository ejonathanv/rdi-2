<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\PanicAlert;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_area_kpis_and_lists(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);
        $checkpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'is_active' => true,
        ]);

        $active = PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::InProgress,
            'started_at' => now()->subMinutes(20),
            'finished_at' => null,
        ]);

        $completed = PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::Completed,
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHour(),
        ]);

        PatrolCheckpointVisit::factory()->create([
            'patrol_run_id' => $completed->id,
            'checkpoint_id' => $checkpoint->id,
            'outcome' => PatrolVisitOutcome::AllClear,
            'is_urgent' => true,
            'urgent_notes' => 'Puerta abierta',
            'reviewed_at' => now()->subMinutes(30),
        ]);

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'message_raw' => 'derrame',
            'message_cleaned' => 'Se reportó un derrame.',
            'is_urgent' => true,
            'created_at' => now()->subMinutes(10),
        ]);

        PanicAlert::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('area.id', $area->id)
                ->where('kpis.urgents_today', 1)
                ->where('kpis.in_progress', 1)
                ->where('kpis.completed_today', 1)
                ->where('kpis.incidents_today', 1)
                ->where('kpis.urgent_incidents_today', 1)
                ->where('kpis.panics_today', 1)
                ->has('recent_urgents', 1)
                ->where('recent_urgents.0.checkpoint', $checkpoint->name)
                ->where('recent_urgents.0.patrol_id', $completed->id)
                ->has('recent_incidents', 1)
                ->where('recent_incidents.0.message', 'Se reportó un derrame.')
                ->has('active_patrols', 1)
                ->where('active_patrols.0.id', $active->id)
                ->has('completed_last_7_days', 7)
                ->has('incidents_last_7_days', 7));
    }

    public function test_dashboard_ignores_other_area_data(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $guard = User::factory()->create();
        $otherRound = Round::factory()->create(['area_id' => $otherArea->id]);
        PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $otherRound->id,
            'status' => PatrolRunStatus::InProgress,
            'started_at' => now(),
            'finished_at' => null,
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('kpis.in_progress', 0)
                ->has('active_patrols', 0));
    }
}
