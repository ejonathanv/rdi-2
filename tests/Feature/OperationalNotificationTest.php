<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\IncidentStatus;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use App\Notifications\IncidentClosedAlert;
use App\Notifications\IncidentCreatedAlert;
use App\Notifications\PanicAlertNotification;
use App\Notifications\UrgentVisitAlert;
use App\Services\IncidentAiProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_panic_notifies_all_area_users_except_actor(): void
    {
        [$guard, $area, $admin, $contact] = $this->areaTeam();

        Http::fake();
        Notification::fake();

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('panic.store'))
            ->assertRedirect(route('guard.home'));

        Notification::assertSentTo($admin, PanicAlertNotification::class);
        Notification::assertSentTo($contact, PanicAlertNotification::class);
        Notification::assertNotSentTo($guard, PanicAlertNotification::class);
    }

    public function test_incident_created_notifies_area_users_in_database(): void
    {
        Storage::fake('public');
        Http::fake();
        Notification::fake();

        [$guard, $area, $admin, $contact, $category] = $this->areaTeamWithCategory();

        $this->mock(IncidentAiProcessor::class, function ($mock) use ($category): void {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn([
                    'cleaned_message' => 'Derrame en pasillo.',
                    'category_code' => $category->code,
                    'new_category' => null,
                ]);
        });

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incidents.store'), [
                'message' => 'hay un derrame',
                'is_urgent' => false,
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ])
            ->assertRedirect(route('guard.home'));

        Notification::assertSentTo($admin, IncidentCreatedAlert::class);
        Notification::assertSentTo($contact, IncidentCreatedAlert::class);
        Notification::assertNotSentTo($guard, IncidentCreatedAlert::class);
    }

    public function test_urgent_visit_notifies_area_users(): void
    {
        Http::fake();
        Notification::fake();

        [$guard, $area, $admin, $contact] = $this->areaTeam();
        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);
        $round->contacts()->attach($contact->id);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession([
                'current_area_id' => $area->id,
                'active_patrol_run_id' => $patrol->id,
            ])
            ->post(route('checkpoints.scan.all-clear', $checkpoint->token), [
                'is_urgent' => true,
                'urgent_notes' => 'Puerta abierta',
            ])
            ->assertRedirect(route('checkpoints.scan.complete', $checkpoint->token));

        Notification::assertSentTo($admin, UrgentVisitAlert::class);
        Notification::assertSentTo($contact, UrgentVisitAlert::class);
        Notification::assertNotSentTo($guard, UrgentVisitAlert::class);
    }

    public function test_incident_closure_notifies_area_users_including_guard(): void
    {
        Http::fake();
        Notification::fake();

        [$guard, $area, $admin, $contact, $category] = $this->areaTeamWithCategory();

        $incident = Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'incident_category_id' => $category->id,
            'status' => IncidentStatus::Nueva,
        ]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('incidencias.status', $incident), [
                'status' => IncidentStatus::Resuelta->value,
                'resolution_notes' => 'Atendido en sitio',
            ])
            ->assertRedirect();

        Notification::assertSentTo($contact, IncidentClosedAlert::class);
        Notification::assertSentTo($guard, IncidentClosedAlert::class);
        Notification::assertNotSentTo($admin, IncidentClosedAlert::class);
    }

    /**
     * @return array{0: User, 1: Area, 2: User, 3: User}
     */
    private function areaTeam(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create(['name' => 'Guardia']);
        $admin = User::factory()->create(['name' => 'Admin']);
        $contact = User::factory()->create([
            'name' => 'Contacto',
            'phone' => '5511111111',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);

        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        return [$guard, $area, $admin, $contact];
    }

    /**
     * @return array{0: User, 1: Area, 2: User, 3: User, 4: IncidentCategory}
     */
    private function areaTeamWithCategory(): array
    {
        [$guard, $area, $admin, $contact] = $this->areaTeam();

        $category = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'code' => 'derrame',
            'name' => 'DERRAME',
        ]);
        $category->contacts()->attach($contact->id);

        return [$guard, $area, $admin, $contact, $category];
    }
}
