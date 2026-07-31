<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoundManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_admin_can_create_round_in_their_area(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('rounds.store'), [
                'area_id' => $area->id,
                'title' => 'Recorrido perimetral',
                'instructions' => 'Revisar accesos y cercas',
                'is_active' => '1',
            ]);

        $round = Round::query()->where('title', 'Recorrido perimetral')->first();

        $this->assertNotNull($round);
        $response->assertRedirect(route('rounds.edit', $round));
    }

    public function test_area_admin_cannot_create_round_in_another_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('rounds.store'), [
                'area_id' => $otherArea->id,
                'title' => 'Recorrido ajeno',
                'is_active' => '1',
            ]);

        $response->assertSessionHasErrors('area_id');
        $this->assertDatabaseMissing('rounds', ['title' => 'Recorrido ajeno']);
    }

    public function test_guard_cannot_manage_rounds(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $response = $this
            ->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rounds.index'));

        $response->assertForbidden();
    }

    public function test_checkpoints_can_be_added_and_reordered(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $round = Round::factory()->create(['area_id' => $area->id]);

        $this->actingAs($admin)
            ->post(route('rounds.checkpoints.store', $round), [
                'name' => 'Entrada',
                'instructions' => 'Revisar portón',
                'is_active' => true,
            ])
            ->assertRedirect(route('rounds.edit', $round));

        $this->actingAs($admin)
            ->post(route('rounds.checkpoints.store', $round), [
                'name' => 'Almacén',
                'instructions' => 'Revisar candados',
                'is_active' => true,
            ])
            ->assertRedirect(route('rounds.edit', $round));

        $checkpoints = $round->checkpoints()->orderBy('position')->get();
        $this->assertCount(2, $checkpoints);
        $this->assertSame('Entrada', $checkpoints[0]->name);
        $this->assertNotEmpty($checkpoints[0]->token);

        $response = $this->actingAs($admin)->put(route('rounds.checkpoints.reorder', $round), [
            'order' => [$checkpoints[1]->id, $checkpoints[0]->id],
        ]);

        $response->assertRedirect(route('rounds.edit', $round));

        $reordered = $round->checkpoints()->orderBy('position')->pluck('name')->all();
        $this->assertSame(['Almacén', 'Entrada'], $reordered);
    }

    public function test_rounds_index_is_scoped_to_current_area(): void
    {
        $areaA = Area::factory()->create(['code' => 'A']);
        $areaB = Area::factory()->create(['code' => 'B']);
        $admin = User::factory()->superAdmin()->create();

        Round::factory()->create(['area_id' => $areaA->id, 'title' => 'Recorrido A']);
        Round::factory()->create(['area_id' => $areaB->id, 'title' => 'Recorrido B']);

        $response = $this
            ->actingAs($admin)
            ->withSession(['current_area_id' => $areaA->id])
            ->get(route('rounds.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('rounds/index')
            ->has('rounds', 1)
            ->where('rounds.0.title', 'Recorrido A'));
    }

    public function test_checkpoint_belongs_to_round_area_isolation(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        $otherAdmin = User::factory()->create();
        $otherArea = Area::factory()->create();
        $otherAdmin->areas()->attach($otherArea->id, ['role' => AreaRole::Admin->value]);

        $response = $this->actingAs($otherAdmin)->put(route('checkpoints.update', $checkpoint), [
            'name' => 'Hack',
            'instructions' => 'No debería',
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }
}
