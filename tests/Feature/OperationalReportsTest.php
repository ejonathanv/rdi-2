<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\IncidentStatus;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperationalReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_volumen_report_filtered_by_dates(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        [$admin, $area, $guard, $category] = $this->adminContext();

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $category->id,
            'status' => IncidentStatus::Nueva,
            'is_urgent' => true,
            'created_at' => Carbon::parse('2026-03-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $category->id,
            'status' => IncidentStatus::Resuelta,
            'created_at' => Carbon::parse('2026-01-01 10:00:00'),
            'updated_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('reportes.volumen', [
                'from' => '2026-03-01',
                'to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('reportes/volumen')
                ->where('report.totals.total', 1)
                ->where('report.totals.urgent', 1)
                ->where('report.totals.open', 1)
                ->where('report.by_category.0.category', 'ROBO')
                ->where('filters.from', '2026-03-01')
                ->where('filters.to', '2026-03-31'));
    }

    public function test_admin_can_view_tiempos_report(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        [$admin, $area, $guard] = $this->adminContext();

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::EnAtencion,
            'created_at' => Carbon::parse('2026-03-10 10:00:00'),
            'acknowledged_at' => Carbon::parse('2026-03-10 10:10:00'),
            'updated_at' => Carbon::parse('2026-03-10 10:10:00'),
        ]);

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::Resuelta,
            'created_at' => Carbon::parse('2026-03-11 10:00:00'),
            'acknowledged_at' => Carbon::parse('2026-03-11 10:05:00'),
            'resolved_at' => Carbon::parse('2026-03-11 11:00:00'),
            'updated_at' => Carbon::parse('2026-03-11 11:00:00'),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('reportes.tiempos', [
                'from' => '2026-03-01',
                'to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('reportes/tiempos')
                ->where('report.summary.with_response', 2)
                ->where('report.summary.with_resolution', 1)
                ->where('report.summary.avg_response_seconds', 450)
                ->where('report.summary.median_response_seconds', 450));
    }

    public function test_admin_can_view_puntos_criticos_report(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        [$admin, $area, $guard] = $this->adminContext();

        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'name' => 'Almacén',
        ]);
        $otherCheckpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'name' => 'Caseta',
        ]);

        Incident::factory()->count(2)->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'checkpoint_id' => $checkpoint->id,
            'created_at' => Carbon::parse('2026-03-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        $patrol = PatrolRun::factory()->create([
            'round_id' => $round->id,
            'user_id' => $guard->id,
        ]);

        PatrolCheckpointVisit::factory()->create([
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'is_urgent' => true,
            'reviewed_at' => Carbon::parse('2026-03-12 10:00:00'),
        ]);

        PatrolCheckpointVisit::factory()->create([
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $otherCheckpoint->id,
            'is_urgent' => true,
            'reviewed_at' => Carbon::parse('2026-03-12 11:00:00'),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('reportes.puntos-criticos', [
                'from' => '2026-03-01',
                'to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('reportes/puntos-criticos')
                ->has('report.rows', 2)
                ->where('report.rows.0.checkpoint', 'Almacén')
                ->where('report.rows.0.incidents', 2)
                ->where('report.rows.0.urgent_visits', 1)
                ->where('report.rows.0.score', 3));
    }

    public function test_guard_cannot_access_reports(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('reportes.volumen'))
            ->assertForbidden();
    }

    public function test_contact_can_access_reports(): void
    {
        $area = Area::factory()->create();
        $contact = User::factory()->create();
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('reportes.volumen'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('reportes/volumen'));
    }

    /**
     * @return array{0: User, 1: Area, 2: User, 3?: IncidentCategory}
     */
    private function adminContext(): array
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $guard = User::factory()->create();
        $category = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'name' => 'Robo',
            'code' => 'ROBO',
        ]);

        return [$admin, $area, $guard, $category];
    }
}
