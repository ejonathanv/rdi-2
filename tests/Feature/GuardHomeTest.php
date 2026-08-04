<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_is_redirected_to_guard_home_after_login(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->post(route('login.store'), [
            'email' => $guard->email,
            'password' => 'password',
        ])->assertRedirect(route('guard.home', absolute: false));
    }

    public function test_admin_is_redirected_to_dashboard_after_login(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_guard_visiting_dashboard_is_redirected_to_guard_home(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->get(route('dashboard'))
            ->assertRedirect(route('guard.home'));
    }

    public function test_guard_can_view_home_and_available_rounds(): void
    {
        $guardArea = Area::factory()->create(['code' => 'G1']);
        $adminArea = Area::factory()->create(['code' => 'A1']);

        $guard = User::factory()->create();
        $guard->areas()->attach([
            $guardArea->id => ['role' => AreaRole::Guard->value],
            $adminArea->id => ['role' => AreaRole::Admin->value],
        ]);

        $visibleRound = Round::factory()->create([
            'area_id' => $guardArea->id,
            'title' => 'Recorrido visible',
            'is_active' => true,
        ]);
        Checkpoint::factory()->create(['round_id' => $visibleRound->id]);

        Round::factory()->create([
            'area_id' => $adminArea->id,
            'title' => 'Recorrido admin',
            'is_active' => true,
        ]);

        Round::factory()->create([
            'area_id' => $guardArea->id,
            'title' => 'Recorrido inactivo',
            'is_active' => false,
        ]);

        // User is also admin of another area, so not "guard only" — still hasGuardRole
        // For home: hasGuardRole allows access. But isGuardOnly is false because canManageAnyArea.
        // Plan: admins go to dashboard. This user is admin+guard so Login → dashboard.
        // But can they access guard.home? Controller allows hasGuardRole — yes.

        $this->actingAs($guard)
            ->get(route('guard.rounds.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('guard/rounds/index')
                ->has('rounds', 1)
                ->where('rounds.0.title', 'Recorrido visible'));
    }

    public function test_pure_guard_sees_only_guard_area_rounds(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();

        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $round = Round::factory()->create([
            'area_id' => $area->id,
            'title' => 'Mi recorrido',
            'is_active' => true,
        ]);
        Round::factory()->create([
            'area_id' => $otherArea->id,
            'title' => 'Ajeno',
            'is_active' => true,
        ]);

        $this->actingAs($guard)
            ->get(route('guard.home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('guard/home'));

        $this->actingAs($guard)
            ->get(route('guard.rounds.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('rounds', 1)
                ->where('rounds.0.id', $round->id));
    }

    public function test_guard_can_start_patrol_for_round_detail(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $round = Round::factory()->create([
            'area_id' => $area->id,
            'is_active' => true,
        ]);
        Checkpoint::factory()->create([
            'round_id' => $round->id,
            'name' => 'Entrada',
            'is_active' => true,
        ]);

        $response = $this->actingAs($guard)
            ->post(route('guard.rounds.start', $round));

        $patrol = PatrolRun::query()->firstOrFail();

        $response->assertRedirect(route('guard.patrols.show', $patrol));

        $this->actingAs($guard)
            ->get(route('guard.patrols.show', $patrol))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('guard/patrols/show')
                ->where('patrol.round.id', $round->id)
                ->has('patrol.checkpoints', 1)
                ->where('patrol.checkpoints.0.name', 'Entrada'));
    }

    public function test_guard_cannot_start_round_from_another_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $round = Round::factory()->create([
            'area_id' => $otherArea->id,
            'is_active' => true,
        ]);

        $this->actingAs($guard)
            ->post(route('guard.rounds.start', $round))
            ->assertForbidden();
    }

    public function test_contact_cannot_access_guard_home(): void
    {
        $area = Area::factory()->create();
        $contact = User::factory()->create();
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $this->actingAs($contact)
            ->get(route('guard.home'))
            ->assertForbidden();
    }
}
