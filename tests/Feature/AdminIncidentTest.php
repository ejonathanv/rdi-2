<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIncidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_view_incidents_for_current_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $guard = User::factory()->create();
        $category = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'name' => 'Robo',
            'code' => 'ROBO',
        ]);

        $incident = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $category->id,
            'message_raw' => 'robo en almacén',
            'message_cleaned' => 'Se reportó un robo en el almacén.',
            'is_urgent' => true,
        ]);

        Incident::factory()->create([
            'area_id' => $otherArea->id,
            'user_id' => $guard->id,
            'message_raw' => 'otra área',
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/index')
                ->has('incidents', 1)
                ->where('incidents.0.id', $incident->id)
                ->where('incidents.0.category', 'ROBO'));

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.show', $incident))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/show')
                ->where('incident.id', $incident->id)
                ->where('incident.message_cleaned', 'Se reportó un robo en el almacén.')
                ->where('incident.is_urgent', true));
    }

    public function test_guard_cannot_access_admin_incidents(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index'))
            ->assertForbidden();
    }
}
