<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_different_roles_in_different_areas(): void
    {
        $areaA = Area::factory()->create(['code' => 'A']);
        $areaB = Area::factory()->create(['code' => 'B']);
        $user = User::factory()->create();

        $user->areas()->attach($areaA->id, ['role' => AreaRole::Guard->value]);
        $user->areas()->attach($areaB->id, ['role' => AreaRole::Contact->value]);

        $user->load('areas');

        $this->assertSame(AreaRole::Guard, $user->roleIn($areaA));
        $this->assertSame(AreaRole::Contact, $user->roleIn($areaB));
        $this->assertTrue($user->canAccessArea($areaA));
        $this->assertTrue($user->canAccessArea($areaB));
        $this->assertFalse($user->canManageArea($areaA));
    }

    public function test_super_admin_can_create_user_with_memberships(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $area = Area::factory()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Guard',
            'email' => 'newguard@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'memberships' => [
                ['area_id' => $area->id, 'role' => AreaRole::Guard->value],
            ],
        ]);

        $response->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'newguard@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame(AreaRole::Guard, $created->roleIn($area));
    }

    public function test_area_admin_can_create_user_in_their_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $areaAdmin = User::factory()->create();
        $areaAdmin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $response = $this->actingAs($areaAdmin)->post(route('users.store'), [
            'name' => 'Plant Guard',
            'email' => 'plantguard@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'memberships' => [
                ['area_id' => $area->id, 'role' => AreaRole::Guard->value],
            ],
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'plantguard@example.com']);

        $forbidden = $this->actingAs($areaAdmin)->post(route('users.store'), [
            'name' => 'Other Guard',
            'email' => 'otherguard@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'memberships' => [
                ['area_id' => $otherArea->id, 'role' => AreaRole::Guard->value],
            ],
        ]);

        $forbidden->assertSessionHasErrors('memberships.0.area_id');
    }

    public function test_guard_cannot_manage_users(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $response = $this->actingAs($guard)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_current_area_can_be_switched(): void
    {
        $areaA = Area::factory()->create(['code' => 'A1']);
        $areaB = Area::factory()->create(['code' => 'B1']);
        $user = User::factory()->create();
        $user->areas()->attach($areaA->id, ['role' => AreaRole::Guard->value]);
        $user->areas()->attach($areaB->id, ['role' => AreaRole::Contact->value]);

        $response = $this->actingAs($user)->put(route('current-area.update'), [
            'area_id' => $areaB->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($areaB->id, session('current_area_id'));
    }
}
