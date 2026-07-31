<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
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

    public function test_guard_cannot_access_areas_index(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $response = $this->actingAs($guard)->get(route('areas.index'));

        $response->assertForbidden();
    }
}
