<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\IncidentStatus;
use App\Models\Area;
use App\Models\Incident;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactOperationsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_view_dashboard_rondines_and_incidencias(): void
    {
        [$contact, $area, $incident, $round] = $this->contactWithOpsData();

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('rondines/index')
                ->has('rounds', 1));

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rondines.rounds.show', $round))
            ->assertOk();

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/index')
                ->has('incidents.data', 1));

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.show', $incident))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('incidencias/show')
                ->where('can_update_status', true)
                ->has('incident.allowed_transitions'));
    }

    public function test_contact_can_update_incident_status(): void
    {
        Http::fake();

        [$contact, $area, $incident] = $this->contactWithOpsData();

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('incidencias.status', $incident), [
                'status' => IncidentStatus::EnAtencion->value,
            ])
            ->assertRedirect(route('incidencias.show', $incident));

        $incident->refresh();
        $this->assertSame(IncidentStatus::EnAtencion, $incident->status);
        $this->assertSame($contact->id, $incident->assigned_to_id);

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('incidencias.status', $incident), [
                'status' => IncidentStatus::Resuelta->value,
                'resolution_notes' => 'Revisado por el contacto del área.',
            ])
            ->assertRedirect(route('incidencias.show', $incident));

        $incident->refresh();
        $this->assertSame(IncidentStatus::Resuelta, $incident->status);
        $this->assertSame($contact->id, $incident->resolved_by_id);
    }

    public function test_contact_cannot_access_admin_modules(): void
    {
        [$contact, $area] = $this->contactWithOpsData();

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('rounds.index'))
            ->assertForbidden();

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incident-categories.index'))
            ->assertForbidden();
    }

    public function test_contact_cannot_view_other_area_incidents(): void
    {
        [$contact, $area] = $this->contactWithOpsData();
        $otherArea = Area::factory()->create();
        $otherIncident = Incident::factory()->create([
            'area_id' => $otherArea->id,
            'status' => IncidentStatus::Nueva,
        ]);

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->get(route('incidencias.show', $otherIncident))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Area, 2: Incident, 3: Round}
     */
    private function contactWithOpsData(): array
    {
        $area = Area::factory()->create();
        $contact = User::factory()->create();
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $round = Round::factory()->create(['area_id' => $area->id]);
        $incident = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'status' => IncidentStatus::Nueva,
        ]);

        return [$contact, $area, $incident, $round];
    }
}
