<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_categories_in_current_area(): void
    {
        [$admin, $area] = $this->adminInArea();

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incident-categories.store'), [
                'area_id' => $area->id,
                'name' => 'Robo',
                'code' => 'robo',
                'description' => 'Sustracción de bienes',
                'is_active' => true,
            ])
            ->assertRedirect();

        $category = IncidentCategory::query()->firstOrFail();

        $this->assertSame('ROBO', $category->code);
        $this->assertSame('ROBO', $category->name);

        $contact = User::factory()->create([
            'phone' => '5598765432',
            'notify_via_whatsapp' => true,
        ]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->put(route('incident-categories.update', $category), [
                'name' => 'Robo',
                'code' => 'ROBO',
                'description' => 'Sustracción de bienes',
                'is_active' => true,
                'contact_ids' => [$contact->id],
            ])
            ->assertRedirect(route('incident-categories.edit', $category));

        $this->assertDatabaseHas('incident_category_contact', [
            'incident_category_id' => $category->id,
            'user_id' => $contact->id,
        ]);
    }

    public function test_guard_cannot_manage_categories(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incident-categories.index'))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Area}
     */
    private function adminInArea(): array
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        return [$admin, $area];
    }
}
