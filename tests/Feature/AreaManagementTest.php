<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_an_area(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->post(route('areas.store'), [
            'name' => 'Planta Sur',
            'code' => 'PLANTA-SUR',
            'location' => 'Guadalajara',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('areas.index'));
        $this->assertDatabaseHas('areas', [
            'name' => 'Planta Sur',
            'code' => 'PLANTA-SUR',
        ]);
    }

    public function test_area_admin_cannot_create_an_area(): void
    {
        $area = Area::factory()->create();
        $areaAdmin = User::factory()->create();
        $areaAdmin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $response = $this->actingAs($areaAdmin)->post(route('areas.store'), [
            'name' => 'Otra Planta',
            'code' => 'OTRA',
            'is_active' => '1',
        ]);

        $response->assertForbidden();
    }

    public function test_create_area_form_includes_default_category_summaries(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('areas.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('areas/create')
                ->has('defaultCategories', 10)
                ->where('defaultCategories.0.name', 'Actos inseguros'));
    }

    public function test_super_admin_can_import_default_categories_when_creating_an_area(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('areas.store'), [
            'name' => 'Planta Sur',
            'code' => 'PLANTA-SUR',
            'location' => 'Guadalajara',
            'is_active' => '1',
            'import_default_categories' => '1',
        ])->assertRedirect(route('areas.index'));

        $area = Area::query()->where('code', 'PLANTA-SUR')->firstOrFail();

        $this->assertSame(10, IncidentCategory::query()->where('area_id', $area->id)->count());
        $this->assertDatabaseHas('incident_categories', [
            'area_id' => $area->id,
            'code' => 'ACTOS_INSEGUROS',
        ]);
        $this->assertNotNull(
            IncidentCategory::query()
                ->where('area_id', $area->id)
                ->where('code', 'ACTOS_INSEGUROS')
                ->value('description'),
        );
    }

    public function test_creating_an_area_without_import_leaves_categories_empty(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('areas.store'), [
            'name' => 'Planta Este',
            'code' => 'PLANTA-ESTE',
            'is_active' => '1',
            'import_default_categories' => '0',
        ])->assertRedirect(route('areas.index'));

        $area = Area::query()->where('code', 'PLANTA-ESTE')->firstOrFail();

        $this->assertSame(0, IncidentCategory::query()->where('area_id', $area->id)->count());
    }

    public function test_guard_cannot_access_areas_index(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $response = $this->actingAs($guard)->get(route('areas.index'));

        $response->assertForbidden();
    }
}
