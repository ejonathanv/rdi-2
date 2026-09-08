<?php

namespace App\Services\Legacy;

use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\CheckpointQuestionOption;
use App\Models\Round;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

class LegacyRoundsImporter
{
    /**
     * @return array{area_id: int, imported: int, skipped: int, forced: int}
     */
    public function import(
        string $absolutePath,
        ?int $areaId = null,
        ?string $createAreaName = null,
        bool $force = false,
    ): array {
        if (! is_file($absolutePath)) {
            throw new RuntimeException("No existe el archivo: {$absolutePath}");
        }

        $contents = file_get_contents($absolutePath);

        if ($contents === false || $contents === '') {
            throw new RuntimeException("No se pudo leer el archivo: {$absolutePath}");
        }

        $xml = @simplexml_load_string($contents);

        if ($xml === false) {
            throw new RuntimeException('El XML de importación no es válido.');
        }

        $area = $this->resolveArea($xml, $areaId, $createAreaName);

        $imported = 0;
        $skipped = 0;
        $forced = 0;

        foreach ($xml->area as $areaNode) {
            foreach ($areaNode->round as $roundNode) {
                $title = trim((string) ($roundNode['title'] ?? ''));

                if ($title === '') {
                    continue;
                }

                $existing = Round::query()
                    ->where('area_id', $area->id)
                    ->where('title', $title)
                    ->first();

                if ($existing !== null) {
                    if (! $force) {
                        $skipped++;

                        continue;
                    }

                    $existing->delete();
                    $forced++;
                }

                DB::transaction(function () use ($area, $roundNode, $title): void {
                    $this->importRound($area, $roundNode, $title);
                });

                $imported++;
            }
        }

        return [
            'area_id' => $area->id,
            'imported' => $imported,
            'skipped' => $skipped,
            'forced' => $forced,
        ];
    }

    private function resolveArea(
        SimpleXMLElement $xml,
        ?int $areaId,
        ?string $createAreaName,
    ): Area {
        if ($areaId !== null) {
            return Area::query()->findOrFail($areaId);
        }

        $nameFromXml = trim((string) ($xml->area[0]['name'] ?? ''));
        $name = trim((string) ($createAreaName ?: $nameFromXml));

        if ($name === '') {
            throw new RuntimeException(
                'Debes indicar --area=ID o --create-area="Nombre" para importar.',
            );
        }

        $code = Str::upper(Str::slug(Str::limit($name, 40, ''), '-'));

        if ($code === '') {
            $code = 'LEGACY-AREA';
        }

        return Area::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'location' => null,
                'is_active' => true,
            ],
        );
    }

    private function importRound(Area $area, SimpleXMLElement $roundNode, string $title): Round
    {
        $round = Round::query()->create([
            'area_id' => $area->id,
            'title' => $title,
            'instructions' => $this->nullableString((string) ($roundNode['instructions'] ?? '')),
            'is_active' => ((string) ($roundNode['is_active'] ?? '1')) !== '0',
        ]);

        $checkpointPosition = 1;

        foreach ($roundNode->checkpoint as $checkpointNode) {
            $name = trim((string) ($checkpointNode['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $position = (int) ($checkpointNode['position'] ?? $checkpointPosition);

            $checkpoint = Checkpoint::query()->create([
                'round_id' => $round->id,
                'name' => $name,
                'instructions' => $this->nullableString((string) ($checkpointNode['instructions'] ?? '')),
                'position' => $position > 0 ? $position : $checkpointPosition,
                'is_active' => true,
            ]);

            $this->importQuestions($checkpoint, $checkpointNode);
            $checkpointPosition++;
        }

        return $round;
    }

    private function importQuestions(Checkpoint $checkpoint, SimpleXMLElement $checkpointNode): void
    {
        $questionPosition = 1;

        foreach ($checkpointNode->question as $questionNode) {
            $body = trim((string) ($questionNode['body'] ?? ''));

            if ($body === '') {
                continue;
            }

            $options = [];

            foreach ($questionNode->option as $optionNode) {
                $label = trim((string) ($optionNode['label'] ?? ''));

                if ($label === '') {
                    continue;
                }

                $options[] = [
                    'label' => $label,
                    'position' => (int) ($optionNode['position'] ?? (count($options) + 1)),
                ];
            }

            if (count($options) < 2) {
                continue;
            }

            $position = (int) ($questionNode['position'] ?? $questionPosition);

            $question = CheckpointQuestion::query()->create([
                'checkpoint_id' => $checkpoint->id,
                'body' => $body,
                'position' => $position > 0 ? $position : $questionPosition,
                'is_active' => true,
            ]);

            foreach ($options as $index => $option) {
                CheckpointQuestionOption::query()->create([
                    'checkpoint_question_id' => $question->id,
                    'label' => $option['label'],
                    'position' => $option['position'] > 0 ? $option['position'] : ($index + 1),
                ]);
            }

            $questionPosition++;
        }
    }

    private function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
