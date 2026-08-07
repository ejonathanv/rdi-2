<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use App\Services\IncidentAiProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_can_report_standalone_incident_from_home(): void
    {
        Storage::fake('public');

        [$guard, $area, $category, $contact] = $this->guardWithCategoryAndContact();

        $this->mock(IncidentAiProcessor::class, function ($mock) use ($category): void {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn([
                    'cleaned_message' => 'Se observó un derrame en el pasillo.',
                    'category_code' => $category->code,
                    'new_category' => null,
                ]);
        });

        Http::fake();

        config([
            'twilio.account_sid' => 'ACtest',
            'twilio.auth_token' => 'token',
            'twilio.sms_from' => '+15550001111',
            'twilio.whatsapp_from' => 'whatsapp:+15550001111',
        ]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incidents.store'), [
                'message' => 'hay un derrame raro',
                'is_urgent' => true,
                'photos' => [
                    UploadedFile::fake()->image('a.jpg'),
                ],
            ])
            ->assertRedirect(route('guard.home'));

        $incident = Incident::query()->firstOrFail();

        $this->assertSame($area->id, $incident->area_id);
        $this->assertNull($incident->patrol_run_id);
        $this->assertNull($incident->checkpoint_id);
        $this->assertTrue($incident->is_urgent);
        $this->assertSame($category->id, $incident->incident_category_id);
        $this->assertSame('Se observó un derrame en el pasillo.', $incident->message_cleaned);
        $this->assertDatabaseCount('incident_photos', 1);
        Http::assertSentCount(1);
    }

    public function test_guard_can_report_incident_linked_to_checkpoint(): void
    {
        Storage::fake('public');
        Http::fake();

        [$guard, $area, $category] = $this->guardWithCategoryAndContact();
        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        $this->mock(IncidentAiProcessor::class, function ($mock) use ($category): void {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn([
                    'cleaned_message' => 'Puerta forzada en el almacén.',
                    'category_code' => $category->code,
                    'new_category' => null,
                ]);
        });

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession([
                'current_area_id' => $area->id,
                'active_patrol_run_id' => $patrol->id,
            ])
            ->post(route('incidents.store'), [
                'message' => 'puerta rota',
                'is_urgent' => false,
                'checkpoint_token' => $checkpoint->token,
            ])
            ->assertRedirect(route('checkpoints.scan.complete', $checkpoint->token));

        $this->assertDatabaseHas('incidents', [
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'incident_category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('patrol_checkpoint_visits', [
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'outcome' => PatrolVisitOutcome::Incident->value,
        ]);
    }

    public function test_ai_failure_still_stores_incident_without_notification(): void
    {
        [$guard, $area] = $this->guardWithCategoryAndContact();

        $this->mock(IncidentAiProcessor::class, function ($mock): void {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn([
                    'cleaned_message' => 'mensaje crudo',
                    'category_code' => null,
                    'new_category' => null,
                ]);
        });

        Http::fake();

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incidents.store'), [
                'message' => 'mensaje crudo',
            ])
            ->assertRedirect(route('guard.home'));

        $this->assertDatabaseHas('incidents', [
            'message_raw' => 'mensaje crudo',
            'incident_category_id' => null,
        ]);

        Http::assertNothingSent();
    }

    public function test_ai_can_create_new_category_when_none_match(): void
    {
        [$guard, $area] = $this->guardWithCategoryAndContact();

        $this->mock(IncidentAiProcessor::class, function ($mock): void {
            $mock->shouldReceive('process')
                ->once()
                ->andReturn([
                    'cleaned_message' => 'Se detectó una fuga de gas en el área de cocina.',
                    'category_code' => null,
                    'new_category' => [
                        'code' => 'FUGA_GAS',
                        'name' => 'Fuga de gas',
                        'description' => 'Olores o fugas de gas detectadas en el sitio.',
                    ],
                ]);
        });

        Http::fake();

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->post(route('incidents.store'), [
                'message' => 'huele a gas en cocina',
            ])
            ->assertRedirect(route('guard.home'));

        $category = IncidentCategory::query()
            ->where('area_id', $area->id)
            ->where('code', 'FUGA_GAS')
            ->firstOrFail();

        $this->assertSame('FUGA DE GAS', $category->name);
        $this->assertSame(
            'Olores o fugas de gas detectadas en el sitio.',
            $category->description,
        );

        $this->assertDatabaseHas('incidents', [
            'message_raw' => 'huele a gas en cocina',
            'incident_category_id' => $category->id,
        ]);

        Http::assertNothingSent();
    }

    public function test_guest_cannot_create_incident(): void
    {
        $this->get(route('incidents.create'))
            ->assertRedirect(route('login'));
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
}
