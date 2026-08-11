<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveUrgentVisitTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_mark_urgent_visit_as_attended(): void
    {
        $area = Area::factory()->create();
        $contact = User::factory()->create();
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);
        $guard = User::factory()->create();

        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);
        $patrol = PatrolRun::factory()->create([
            'round_id' => $round->id,
            'user_id' => $guard->id,
        ]);
        $visit = PatrolCheckpointVisit::factory()->create([
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'outcome' => PatrolVisitOutcome::AllClear,
            'is_urgent' => true,
            'urgent_notes' => 'Puerta abierta',
        ]);

        $this->actingAs($contact)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('rondines.visits.resolve-urgent', [$round, $patrol, $visit]))
            ->assertRedirect(route('rondines.patrols.show', [$round, $patrol]));

        $this->assertNotNull($visit->fresh()->urgent_resolved_at);
        $this->assertSame($contact->id, $visit->fresh()->urgent_resolved_by_id);
    }

    public function test_guard_cannot_mark_urgent_visit_as_attended(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);
        $patrol = PatrolRun::factory()->create([
            'round_id' => $round->id,
            'user_id' => $guard->id,
        ]);
        $visit = PatrolCheckpointVisit::factory()->create([
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'is_urgent' => true,
        ]);

        $this->actingAs($guard)
            ->withSession(['current_area_id' => $area->id])
            ->patch(route('rondines.visits.resolve-urgent', [$round, $patrol, $visit]))
            ->assertForbidden();
    }
}
