<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_new_user_sees_tour_incomplete_in_shared_props(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create(['product_tour_completed_at' => null]);
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.product_tour_eligible', true)
                ->where('auth.user.product_tour_completed', false));
    }

    public function test_admin_and_contact_can_complete_tour_idempotently(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create(['product_tour_completed_at' => null]);
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $this->actingAs($admin)
            ->from(route('dashboard'))
            ->post(route('product-tour.complete'))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($admin->fresh()->product_tour_completed_at);

        $completedAt = $admin->fresh()->product_tour_completed_at;

        $this->actingAs($admin)
            ->from(route('dashboard'))
            ->post(route('product-tour.complete'))
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($admin->fresh()->product_tour_completed_at->equalTo($completedAt));

        $contact = User::factory()->create(['product_tour_completed_at' => null]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $this->actingAs($contact)
            ->from(route('dashboard'))
            ->post(route('product-tour.complete'))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($contact->fresh()->product_tour_completed_at);
    }

    public function test_guard_cannot_complete_product_tour(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create(['product_tour_completed_at' => null]);
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->post(route('product-tour.complete'))
            ->assertForbidden();

        $this->assertNull($guard->fresh()->product_tour_completed_at);
    }

    public function test_guard_is_not_eligible_for_product_tour_props(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create(['product_tour_completed_at' => null]);
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->assertFalse($guard->isEligibleForProductTour());
        $this->assertTrue($guard->isGuardOnly());
    }

    public function test_contact_is_eligible_for_product_tour(): void
    {
        $area = Area::factory()->create();
        $contact = User::factory()->create(['product_tour_completed_at' => null]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $this->assertTrue($contact->isEligibleForProductTour());
        $this->assertFalse($contact->hasCompletedProductTour());
    }
}
