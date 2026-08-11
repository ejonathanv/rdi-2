<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use App\Services\IncidentAiProcessor;
use App\Services\IncidentNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExternalSideEffectResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_still_saves_when_twilio_connection_fails(): void
    {
        Storage::fake('public');

        [$guard, $area, $category] = $this->guardWithCategoryAndContact();

        $this->mock(IncidentAiProcessor::class, function ($mock) use ($category): void {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn([
                    'cleaned_message' => 'Derrame limpio.',
                    'category_code' => $category->code,
                    'new_category' => null,
                ]);
        });

        config([
            'twilio.account_sid' => 'ACtest',
            'twilio.auth_token' => 'token',
            'twilio.sms_from' => '+15550001111',
            'twilio.whatsapp_from' => 'whatsapp:+15550001111',
        ]);

        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incidents.store'), [
                'message' => 'hay un derrame',
                'is_urgent' => true,
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ])
            ->assertRedirect(route('guard.home'));

        $incident = Incident::query()->firstOrFail();

        $this->assertSame($category->id, $incident->incident_category_id);
        $this->assertSame('Derrame limpio.', $incident->message_cleaned);
        $this->assertDatabaseCount('incident_photos', 1);
    }

    public function test_urgent_checkpoint_visit_still_saves_when_twilio_fails(): void
    {
        [$guard, $round, $checkpoint, $contact] = $this->guardWithUrgentContact();

        config([
            'twilio.account_sid' => 'ACtest',
            'twilio.auth_token' => 'token',
            'twilio.sms_from' => '+15550001111',
            'twilio.whatsapp_from' => 'whatsapp:+15550001111',
        ]);

        Http::fake(function () {
            throw new ConnectionException('Twilio unreachable');
        });

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession([
                'current_area_id' => $round->area_id,
                'active_patrol_run_id' => $patrol->id,
            ])
            ->post(route('checkpoints.scan.all-clear', $checkpoint->token), [
                'is_urgent' => true,
                'urgent_notes' => 'Puerta abierta',
            ])
            ->assertRedirect(route('checkpoints.scan.complete', $checkpoint->token));

        $this->assertDatabaseHas('patrol_checkpoint_visits', [
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'is_urgent' => true,
            'urgent_notes' => 'Puerta abierta',
        ]);

        $this->assertSame(1, PatrolCheckpointVisit::query()->count());
        $this->assertTrue($contact->is($round->contacts()->first()));
    }

    public function test_incident_still_saves_when_notifier_throws(): void
    {
        Storage::fake('public');

        [$guard, $area, $category] = $this->guardWithCategoryAndContact();

        $this->mock(IncidentAiProcessor::class, function ($mock) use ($category): void {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn([
                    'cleaned_message' => 'Incidencia procesada.',
                    'category_code' => $category->code,
                    'new_category' => null,
                ]);
        });

        $this->mock(IncidentNotifier::class, function ($mock): void {
            $mock->shouldReceive('notify')
                ->once()
                ->andThrow(new \RuntimeException('Fallo inesperado al notificar'));
        });

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incidents.store'), [
                'message' => 'algo pasó',
            ])
            ->assertRedirect(route('guard.home'));

        $this->assertDatabaseHas('incidents', [
            'message_raw' => 'algo pasó',
            'incident_category_id' => $category->id,
            'message_cleaned' => 'Incidencia procesada.',
        ]);
    }

    /**
     * @return array{0: User, 1: Area, 2: IncidentCategory, 3: User}
     */
    private function guardWithCategoryAndContact(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $contact = User::factory()->create([
            'phone' => '5512345678',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $category = IncidentCategory::factory()->create([
            'area_id' => $area->id,
            'code' => 'DERRAME',
            'name' => 'Derrame',
        ]);
        $category->contacts()->attach($contact->id);

        return [$guard, $area, $category, $contact];
    }

    /**
     * @return array{0: User, 1: Round, 2: Checkpoint, 3: User}
     */
    private function guardWithUrgentContact(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);
        $checkpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'is_active' => true,
        ]);

        $contact = User::factory()->create([
            'phone' => '5598765432',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);
        $round->contacts()->attach($contact->id);

        return [$guard, $round, $checkpoint, $contact];
    }
}
