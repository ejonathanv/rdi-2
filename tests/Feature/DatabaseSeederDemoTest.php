<?php

namespace Tests\Feature;

use App\Enums\IncidentStatus;
use App\Enums\PatrolRunStatus;
use App\Models\Checkpoint;
use App\Models\Incident;
use App\Models\PanicAlert;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_rich_operational_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'guard@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'guard2@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'contact@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'contact2@example.com']);

        $this->assertGreaterThanOrEqual(5, Round::query()->count());
        $this->assertGreaterThanOrEqual(15, Checkpoint::query()->count());

        Round::query()->withCount('checkpoints')->each(function (Round $round): void {
            $this->assertGreaterThanOrEqual(3, $round->checkpoints_count);
            $this->assertLessThanOrEqual(8, $round->checkpoints_count);
        });

        $this->assertTrue(
            Checkpoint::query()->whereHas('questions.options')->exists(),
        );

        $this->assertTrue(
            PatrolRun::query()->where('status', PatrolRunStatus::Completed)->exists(),
        );
        $this->assertTrue(
            PatrolRun::query()->where('status', PatrolRunStatus::InProgress)->exists(),
        );
        $this->assertGreaterThan(0, PatrolCheckpointVisit::query()->count());

        $this->assertTrue(Incident::query()->where('status', IncidentStatus::Nueva)->exists());
        $this->assertTrue(Incident::query()->where('status', IncidentStatus::EnAtencion)->exists());
        $this->assertTrue(Incident::query()->where('status', IncidentStatus::Resuelta)->exists());
        $this->assertTrue(Incident::query()->where('status', IncidentStatus::Descartada)->exists());

        $this->assertGreaterThanOrEqual(2, PanicAlert::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@example.com')->firstOrFail()->is_super_admin);
    }
}
