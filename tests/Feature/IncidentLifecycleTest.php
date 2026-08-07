<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\IncidentStatus;
use App\Models\Area;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\User;
use App\Services\IncidentAiProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IncidentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_incident_starts_as_nueva(): void
    {
        [$guard, $area, $category] = $this->guardWithCategory();

        $this->mock(IncidentAiProcessor::class, function ($mock) use ($category): void {
            $mock->shouldReceive('process')->once()->andReturn([
                'cleaned_message' => 'Reporte limpio',
                'category_code' => $category->code,
                'new_category' => null,
            ]);
        });

        Http::fake();

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incidents.store'), [
                'message' => 'algo pasó',
            ])
            ->assertRedirect(route('guard.home'));

        $this->assertDatabaseHas('incidents', [
            'message_raw' => 'algo pasó',
            'status' => IncidentStatus::Nueva->value,
        ]);
    }

    public function test_admin_can_take_and_resolve_incident(): void
    {
        [$admin, $area, $guard, $category, $contact] = $this->adminAreaWithIncidentActors();

        $incident = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $category->id,
            'status' => IncidentStatus::Nueva,
            'message_cleaned' => 'Puerta abierta',
        ]);

        Http::fake();

        config([
            'twilio.account_sid' => 'ACtest',
            'twilio.auth_token' => 'token',
            'twilio.sms_from' => '+15550001111',
            'twilio.whatsapp_from' => 'whatsapp:+15550001111',
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('incidencias.status', $incident), [
                'status' => IncidentStatus::EnAtencion->value,
            ])
            ->assertRedirect(route('incidencias.show', $incident));

        $incident->refresh();
        $this->assertSame(IncidentStatus::EnAtencion, $incident->status);
        $this->assertSame($admin->id, $incident->assigned_to_id);
        $this->assertNotNull($incident->acknowledged_at);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('incidencias.status', $incident), [
                'status' => IncidentStatus::Resuelta->value,
                'resolution_notes' => 'Se aseguró la puerta.',
            ])
            ->assertRedirect(route('incidencias.show', $incident));

        $incident->refresh();
        $this->assertSame(IncidentStatus::Resuelta, $incident->status);
        $this->assertSame($admin->id, $incident->resolved_by_id);
        $this->assertSame('Se aseguró la puerta.', $incident->resolution_notes);
        $this->assertNotNull($incident->resolved_at);

        Http::assertSent(function ($request) use ($contact) {
            return str_contains($request->url(), 'api.twilio.com')
                && str_contains((string) $request['Body'], 'Resuelta')
                && str_contains((string) $request['To'], $contact->phone);
        });
    }

    public function test_admin_can_filter_incidents_by_status(): void
    {
        [$admin, $area, $guard] = $this->adminAreaWithIncidentActors();

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::Nueva,
        ]);
        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::Resuelta,
            'resolved_at' => now(),
            'resolution_notes' => 'ok',
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index', ['status' => IncidentStatus::Nueva->value]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/index')
                ->has('incidents', 1)
                ->where('incidents.0.status', IncidentStatus::Nueva->value)
                ->where('filters.status', IncidentStatus::Nueva->value));
    }

    public function test_guard_cannot_update_incident_status(): void
    {
        [$admin, $area, $guard] = $this->adminAreaWithIncidentActors();

        $incident = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::Nueva,
        ]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('incidencias.status', $incident), [
                'status' => IncidentStatus::EnAtencion->value,
            ])
            ->assertForbidden();
    }

    public function test_resolution_notes_are_required_to_close(): void
    {
        [$admin, $area, $guard] = $this->adminAreaWithIncidentActors();

        $incident = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::Nueva,
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->from(route('incidencias.show', $incident))
            ->patch(route('incidencias.status', $incident), [
                'status' => IncidentStatus::Resuelta->value,
            ])
            ->assertRedirect(route('incidencias.show', $incident))
            ->assertSessionHasErrors('resolution_notes');
    }

    /**
     * @return array{0: User, 1: Area, 2: IncidentCategory}
     */
    private function guardWithCategory(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $category = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'code' => 'ROBO',
        ]);

        return [$guard, $area, $category];
    }

    /**
     * @return array{0: User, 1: Area, 2: User, 3: IncidentCategory, 4: User}
     */
    private function adminAreaWithIncidentActors(): array
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $guard = User::factory()->create([
            'phone' => '5511111111',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $contact = User::factory()->create([
            'phone' => '5522222222',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $category = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'code' => 'ACCESO',
        ]);
        $category->contacts()->attach($contact->id);

        return [$admin, $area, $guard, $category, $contact];
    }
}
