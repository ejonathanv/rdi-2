<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\IncidentStatus;
use App\Models\Area;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IncidentFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_incidents_by_date_range_and_category(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $guard = User::factory()->create();
        $robo = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'name' => 'Robo',
            'code' => 'ROBO',
        ]);
        $acceso = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'name' => 'Acceso',
            'code' => 'ACCESO',
        ]);

        $inRange = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $robo->id,
            'status' => IncidentStatus::Nueva,
            'created_at' => Carbon::parse('2026-03-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $robo->id,
            'status' => IncidentStatus::Nueva,
            'created_at' => Carbon::parse('2026-02-01 10:00:00'),
            'updated_at' => Carbon::parse('2026-02-01 10:00:00'),
        ]);

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $acceso->id,
            'status' => IncidentStatus::Nueva,
            'created_at' => Carbon::parse('2026-03-12 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-12 10:00:00'),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index', [
                'from' => '2026-03-01',
                'to' => '2026-03-31',
                'category_id' => $robo->id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/index')
                ->has('incidents.data', 1)
                ->where('incidents.data.0.id', $inRange->id)
                ->where('filters.from', '2026-03-01')
                ->where('filters.to', '2026-03-31')
                ->where('filters.category_id', $robo->id)
                ->has('category_options', 2));
    }

    public function test_category_filter_must_belong_to_current_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $foreignCategory = IncidentCategory::factory()->create([
            'area_id' => $otherArea->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->from(route('incidencias.index'))
            ->get(route('incidencias.index', [
                'category_id' => $foreignCategory->id,
            ]))
            ->assertRedirect(route('incidencias.index'))
            ->assertSessionHasErrors('category_id');
    }

    public function test_status_filter_still_works_with_date_filters(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $guard = User::factory()->create();

        $match = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::EnAtencion,
            'created_at' => Carbon::parse('2026-03-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 10:00:00'),
        ]);

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::Nueva,
            'created_at' => Carbon::parse('2026-03-10 11:00:00'),
            'updated_at' => Carbon::parse('2026-03-10 11:00:00'),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index', [
                'status' => IncidentStatus::EnAtencion->value,
                'from' => '2026-03-01',
                'to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/index')
                ->has('incidents.data', 1)
                ->where('incidents.data.0.id', $match->id)
                ->where('filters.status', IncidentStatus::EnAtencion->value));
    }

    public function test_incidents_are_paginated_twenty_per_page(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $guard = User::factory()->create();

        Incident::factory()->count(25)->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/index')
                ->has('incidents.data', 20)
                ->where('incidents.per_page', 20)
                ->where('incidents.total', 25)
                ->where('incidents.current_page', 1)
                ->where('incidents.last_page', 2));

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/index')
                ->has('incidents.data', 5)
                ->where('incidents.current_page', 2));
    }
}
