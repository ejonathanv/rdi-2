<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Enums\IncidentStatus;
use App\Enums\PatrolRunStatus;
use App\Mail\DailyPatrolsDigestMail;
use App\Mail\OpenUrgentsDigestMail;
use App\Mail\WeeklyIncidentsDigestMail;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DigestMailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_urgents_digest_is_queued_for_contacts_only_when_there_are_items(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-03-11 15:00:00');

        [$area, $contact, $admin, $guard] = $this->areaWithPeople();

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'is_urgent' => true,
            'status' => IncidentStatus::Nueva,
        ]);

        $this->artisan('reports:send-open-urgents-digest')->assertSuccessful();

        Mail::assertQueued(OpenUrgentsDigestMail::class, function (OpenUrgentsDigestMail $mail) use ($contact, $admin) {
            return $mail->hasTo($contact->email)
                && ! $mail->hasTo($admin->email)
                && count($mail->digest['incidents']) === 1;
        });
    }

    public function test_open_urgents_digest_is_skipped_when_empty(): void
    {
        Mail::fake();

        $this->areaWithPeople();

        $this->artisan('reports:send-open-urgents-digest')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_weekly_incidents_digest_is_always_queued_for_contacts(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-03-13 13:00:00', 'America/Mexico_City')->utc());

        [$area, $contact, , $guard] = $this->areaWithPeople();

        Incident::factory()->create([
            'area_id' => $area->id,
            'user_id' => $guard->id,
            'created_at' => Carbon::parse('2026-03-11 10:00:00', 'America/Mexico_City')->utc(),
            'updated_at' => Carbon::parse('2026-03-11 10:00:00', 'America/Mexico_City')->utc(),
        ]);

        $this->artisan('reports:send-weekly-incidents-digest')->assertSuccessful();

        Mail::assertQueued(WeeklyIncidentsDigestMail::class, function (WeeklyIncidentsDigestMail $mail) use ($contact) {
            return $mail->hasTo($contact->email)
                && $mail->digest['totals']['total'] === 1;
        });
    }

    public function test_daily_patrols_digest_is_queued_when_there_are_patrols(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-03-11 20:00:00', 'America/Mexico_City')->utc());

        [$area, $contact, , $guard] = $this->areaWithPeople();
        $round = Round::factory()->create(['area_id' => $area->id]);

        PatrolRun::factory()->create([
            'round_id' => $round->id,
            'user_id' => $guard->id,
            'status' => PatrolRunStatus::Completed,
            'started_at' => Carbon::parse('2026-03-11 09:00:00', 'America/Mexico_City')->utc(),
            'finished_at' => Carbon::parse('2026-03-11 10:00:00', 'America/Mexico_City')->utc(),
        ]);

        $this->artisan('reports:send-daily-patrols-digest')->assertSuccessful();

        Mail::assertQueued(DailyPatrolsDigestMail::class, function (DailyPatrolsDigestMail $mail) use ($contact) {
            return $mail->hasTo($contact->email)
                && count($mail->digest['patrols']) === 1;
        });
    }

    public function test_resolved_urgent_visits_are_excluded_from_open_urgents_digest(): void
    {
        Mail::fake();

        [$area, , , $guard] = $this->areaWithPeople();
        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);
        $patrol = PatrolRun::factory()->create([
            'round_id' => $round->id,
            'user_id' => $guard->id,
        ]);

        PatrolCheckpointVisit::factory()->create([
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'is_urgent' => true,
            'urgent_resolved_at' => now(),
            'urgent_resolved_by_id' => $guard->id,
        ]);

        $this->artisan('reports:send-open-urgents-digest')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    /**
     * @return array{0: Area, 1: User, 2: User, 3: User}
     */
    private function areaWithPeople(): array
    {
        $area = Area::factory()->create(['is_active' => true]);
        $contact = User::factory()->create();
        $admin = User::factory()->create();
        $guard = User::factory()->create();

        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        return [$area, $contact, $admin, $guard];
    }
}
