<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\Round;
use App\Services\Legacy\LegacyRoundsImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportLegacyRoundsTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_rounds_checkpoints_and_questions_from_xml(): void
    {
        $area = Area::factory()->create([
            'name' => 'Planta Destino',
            'code' => 'PLANTA-DESTINO',
        ]);

        $path = $this->writeSampleXml();

        $result = app(LegacyRoundsImporter::class)->import(
            absolutePath: $path,
            areaId: $area->id,
        );

        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['skipped']);

        $round = Round::query()->where('area_id', $area->id)->where('title', 'Recorrido demo')->first();

        $this->assertNotNull($round);
        $this->assertSame('Instrucciones del recorrido', $round->instructions);

        $checkpoint = Checkpoint::query()->where('round_id', $round->id)->where('name', 'Lobby')->first();

        $this->assertNotNull($checkpoint);
        $this->assertSame(1, $checkpoint->position);
        $this->assertNotEmpty($checkpoint->token);

        $question = CheckpointQuestion::query()
            ->where('checkpoint_id', $checkpoint->id)
            ->where('body', '¿Las puertas funcionan?')
            ->first();

        $this->assertNotNull($question);
        $this->assertCount(2, $question->options);
        $this->assertSame(['Si', 'No'], $question->options->pluck('label')->all());
    }

    public function test_skips_existing_round_unless_force_is_enabled(): void
    {
        $area = Area::factory()->create();
        Round::factory()->create([
            'area_id' => $area->id,
            'title' => 'Recorrido demo',
        ]);

        $path = $this->writeSampleXml();
        $importer = app(LegacyRoundsImporter::class);

        $skipped = $importer->import(absolutePath: $path, areaId: $area->id);

        $this->assertSame(0, $skipped['imported']);
        $this->assertSame(1, $skipped['skipped']);
        $this->assertSame(1, Round::query()->where('area_id', $area->id)->count());

        $forced = $importer->import(absolutePath: $path, areaId: $area->id, force: true);

        $this->assertSame(1, $forced['imported']);
        $this->assertSame(1, $forced['forced']);
        $this->assertSame(1, Round::query()->where('area_id', $area->id)->count());
        $this->assertDatabaseHas('checkpoints', ['name' => 'Lobby']);
    }

    public function test_artisan_import_command_creates_area_when_requested(): void
    {
        $path = $this->writeSampleXml();

        $this->artisan('rdi:import-legacy-rounds', [
            'path' => $path,
            '--create-area' => 'STRYKER EAGLE',
        ])->assertSuccessful();

        $this->assertDatabaseHas('areas', [
            'name' => 'STRYKER EAGLE',
            'code' => 'STRYKER-EAGLE',
        ]);

        $this->assertDatabaseHas('rounds', [
            'title' => 'Recorrido demo',
        ]);
    }

    public function test_questions_with_fewer_than_two_options_are_skipped(): void
    {
        $area = Area::factory()->create();
        $path = storage_path('framework/testing/legacy-invalid-options.xml');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rdi version="2" source="test" exported_at="2026-01-01T00:00:00+00:00">
  <area name="Test" legacy_group_id="1">
    <round title="Recorrido incompleto" instructions="" legacy_route_id="1" is_active="1">
      <checkpoint name="Punto" position="1" instructions="" legacy_zone_id="1" legacy_qr="zone-1">
        <question body="¿Solo una opción?" position="1" legacy_id="1" guide="">
          <option label="Si" position="1" legacy_severity="1" legacy_needs_report="0"/>
        </question>
      </checkpoint>
    </round>
  </area>
</rdi>
XML);

        app(LegacyRoundsImporter::class)->import(absolutePath: $path, areaId: $area->id);

        $this->assertDatabaseHas('rounds', ['title' => 'Recorrido incompleto']);
        $this->assertDatabaseHas('checkpoints', ['name' => 'Punto']);
        $this->assertDatabaseCount('checkpoint_questions', 0);
    }

    private function writeSampleXml(): string
    {
        $path = storage_path('framework/testing/legacy-sample.xml');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rdi version="2" source="stkrrdi" exported_at="2026-01-01T00:00:00+00:00">
  <area name="STRYKER EAGLE" legacy_group_id="7">
    <round title="Recorrido demo" instructions="Instrucciones del recorrido" legacy_route_id="6" is_active="1">
      <checkpoint name="Lobby" position="1" instructions="" legacy_zone_id="1" legacy_qr="zone-1">
        <question body="¿Las puertas funcionan?" position="1" legacy_id="1" guide="">
          <option label="Si" position="1" legacy_severity="1" legacy_needs_report="0"/>
          <option label="No" position="2" legacy_severity="3" legacy_needs_report="1"/>
        </question>
      </checkpoint>
    </round>
  </area>
</rdi>
XML);

        return $path;
    }
}
