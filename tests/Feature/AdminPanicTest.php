<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\PanicAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminPanicTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_panic_alerts_by_day(): void
    {
        Carbon::setTestNow('2026-03-15 12:00:00');

        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $guard = User::factory()->create();

        $today = PanicAlert::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'created_at' => Carbon::parse('2026-03-15 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-15 10:00:00'),
        ]);

        PanicAlert::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'created_at' => Carbon::parse('2026-03-14 10:00:00'),
            'updated_at' => Carbon::parse('2026-03-14 10:00:00'),
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('panicos.index', [
                'from' => '2026-03-15',
                'to' => '2026-03-15',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('panicos/index')
                ->has('alerts.data', 1)
                ->where('alerts.data.0.id', $today->id)
                ->where('filters.from', '2026-03-15')
                ->where('filters.to', '2026-03-15'));
    }

    public function test_guard_cannot_access_panic_alerts(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('panicos.index'))
            ->assertForbidden();
    }
}
