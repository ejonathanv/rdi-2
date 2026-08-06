<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UrgentVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_urgent_questionnaire_stores_flag_and_notifies_assigned_contact(): void
    {
        Http::fake();

        config([
            'twilio.account_sid' => 'ACtest',
            'twilio.auth_token' => 'token',
            'twilio.sms_from' => '+15550001111',
            'twilio.whatsapp_from' => 'whatsapp:+15550001111',
        ]);

        [$guard, $round, $checkpoint, $question, $option, $contact] = $this->guardWithQuestionAndContact();

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession(['active_patrol_run_id' => $patrol->id])
            ->post(route('checkpoints.scan.store', $checkpoint->token), [
                'answers' => [$question->id => $option->id],
                'is_urgent' => true,
                'urgent_notes' => 'Puerta trasera abierta',
            ])
            ->assertRedirect(route('checkpoints.scan.complete', $checkpoint->token));

        $this->assertDatabaseHas('patrol_checkpoint_visits', [
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'is_urgent' => true,
            'urgent_notes' => 'Puerta trasera abierta',
        ]);

        Http::assertSentCount(1);
    }

    public function test_urgent_visit_requires_notes(): void
    {
        [$guard, $round, $checkpoint] = $this->guardWithCheckpoint();

        $this->actingAs($guard)->post(route('guard.rounds.start', $round));
        $patrol = PatrolRun::query()->firstOrFail();

        $this->actingAs($guard)
            ->withSession(['active_patrol_run_id' => $patrol->id])
            ->from(route('checkpoints.scan.show', $checkpoint->token))
            ->post(route('checkpoints.scan.all-clear', $checkpoint->token), [
                'is_urgent' => true,
            ])
            ->assertSessionHasErrors('urgent_notes');
    }

    public function test_contact_requires_phone_when_notifications_enabled(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Contacto sin teléfono',
                'email' => 'contacto-sin-tel@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'notify_via_whatsapp' => true,
                'notify_via_sms' => false,
                'memberships' => [
                    ['area_id' => $area->id, 'role' => AreaRole::Contact->value],
                ],
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_round_can_sync_assigned_contacts(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $contact = User::factory()->create([
            'phone' => '5512345678',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $round = Round::factory()->create(['area_id' => $area->id]);

        $this->actingAs($admin)
            ->withSession(['current_area_id' => $area->id])
            ->put(route('rounds.update', $round), [
                'title' => $round->title,
                'instructions' => $round->instructions,
                'is_active' => true,
                'contact_ids' => [$contact->id],
            ])
            ->assertRedirect(route('rounds.edit', $round));

        $this->assertDatabaseHas('round_contact', [
            'round_id' => $round->id,
            'user_id' => $contact->id,
        ]);
    }

    /**
     * @return array{0: User, 1: Round, 2: Checkpoint}
     */
    private function guardWithCheckpoint(): array
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $round = Round::factory()->create(['area_id' => $area->id, 'is_active' => true]);
        $checkpoint = Checkpoint::factory()->create([
            'round_id' => $round->id,
            'is_active' => true,
        ]);

        return [$guard, $round, $checkpoint];
    }

    /**
     * @return array{0: User, 1: Round, 2: Checkpoint, 3: CheckpointQuestion, 4: mixed, 5: User}
     */
    private function guardWithQuestionAndContact(): array
    {
        [$guard, $round, $checkpoint] = $this->guardWithCheckpoint();

        $question = CheckpointQuestion::factory()->create([
            'checkpoint_id' => $checkpoint->id,
            'is_active' => true,
            'position' => 1,
        ]);
        $option = $question->options()->create([
            'label' => 'Sí',
            'position' => 1,
        ]);

        $contact = User::factory()->create([
            'phone' => '5512345678',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);
        $contact->areas()->attach($round->area_id, ['role' => AreaRole::Contact->value]);
        $round->contacts()->attach($contact->id);

        return [$guard, $round, $checkpoint, $question, $option, $contact];
    }
}
