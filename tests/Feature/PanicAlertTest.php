<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolRunStatus;
use App\Models\Area;
use App\Models\PanicAlert;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PanicAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_can_trigger_panic_and_notify_area_contacts(): void
    {
        [$guard, $area, $contact] = $this->guardWithAreaContact();
        $otherArea = Area::factory()->create();
        $otherContact = User::factory()->create([
            'phone' => '5599999999',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $otherContact->areas()->attach($otherArea->id, ['role' => AreaRole::Contact->value]);

        Http::fake();

        config([
            'twilio.account_sid' => 'ACtest',
            'twilio.auth_token' => 'token',
            'twilio.sms_from' => '+15550001111',
            'twilio.whatsapp_from' => 'whatsapp:+15550001111',
        ]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('panic.store'))
            ->assertRedirect(route('guard.home'));

        $alert = PanicAlert::query()->firstOrFail();

        $this->assertSame($area->id, $alert->area_id);
        $this->assertSame($guard->id, $alert->user_id);
        $this->assertNull($alert->patrol_run_id);

        Http::assertSent(function ($request) use ($contact) {
            return str_contains($request->url(), 'api.twilio.com')
                && str_contains((string) $request['Body'], 'BOTÓN DE PÁNICO')
                && str_contains((string) $request['To'], $contact->phone);
        });

        Http::assertNotSent(function ($request) use ($otherContact) {
            return str_contains((string) ($request['To'] ?? ''), $otherContact->phone);
        });
    }

    public function test_panic_links_active_patrol_when_present(): void
    {
        [$guard, $area] = $this->guardWithAreaContact();
        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);
        $patrol = PatrolRun::factory()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::InProgress,
            'started_at' => now()->subMinutes(5),
            'finished_at' => null,
        ]);

        Http::fake();

        $this->actingAs($guard)
            ->withSession([
                'current_area_id' => $area->id,
                'active_patrol_run_id' => $patrol->id,
            ])
            ->post(route('panic.store'))
            ->assertRedirect(route('guard.home'));

        $this->assertDatabaseHas('panic_alerts', [
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'patrol_run_id' => $patrol->id,
        ]);
    }

    public function test_guest_cannot_trigger_panic(): void
    {
        $this->post(route('panic.store'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_without_guard_role_cannot_trigger_panic(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('panic.store'))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Area, 2: User}
     */
    private function guardWithAreaContact(): array
    {
        $area = Area::factory()->create(['name' => 'Planta Norte']);
        $guard = User::factory()->create(['name' => 'Juan Guardia']);
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $contact = User::factory()->create([
            'phone' => '5512345678',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        return [$guard, $area, $contact];
    }
}
