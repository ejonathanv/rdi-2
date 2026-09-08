<?php

namespace App\Services\Legacy;

use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyRoundsExporter
{
    /**
     * Export legacy routes, zones and questionnaires to an XML file.
     *
     * @return array{path: string, absolute_path: string, rounds: int, checkpoints: int, questions: int}
     */
    public function export(?string $relativePath = null): array
    {
        $relativePath ??= 'exports/legacy-rounds-'.now()->format('Y-m-d-His').'.xml';
        $absolutePath = storage_path('app/'.$relativePath);

        $directory = dirname($absolutePath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("No se pudo crear el directorio: {$directory}");
        }

        $xml = $this->buildXml();

        if (file_put_contents($absolutePath, $xml) === false) {
            throw new RuntimeException("No se pudo escribir el archivo: {$absolutePath}");
        }

        return [
            'path' => $relativePath,
            'absolute_path' => $absolutePath,
            'rounds' => substr_count($xml, '<round '),
            'checkpoints' => substr_count($xml, '<checkpoint '),
            'questions' => substr_count($xml, '<question '),
        ];
    }

    public function buildXml(): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->createElement('rdi');
        $root->setAttribute('version', '2');
        $root->setAttribute('source', (string) config('database.connections.legacy.database'));
        $root->setAttribute('exported_at', now()->toIso8601String());
        $document->appendChild($root);

        $groups = DB::connection('legacy')
            ->table('groups')
            ->orderBy('id')
            ->get();

        $primaryGroup = $groups->first();

        $area = $document->createElement('area');
        $area->setAttribute('name', $primaryGroup ? (string) $primaryGroup->name : 'Legacy');
        $area->setAttribute(
            'legacy_group_id',
            $primaryGroup ? (string) $primaryGroup->id : '',
        );
        $root->appendChild($area);

        $this->appendRounds($document, $area);

        $xml = $document->saveXML();

        if ($xml === false) {
            throw new RuntimeException('No se pudo generar el XML de exportación legacy.');
        }

        return $xml;
    }

    private function appendRounds(DOMDocument $document, DOMElement $areaNode): void
    {
        $routes = DB::connection('legacy')
            ->table('routes')
            ->orderBy('id')
            ->get();

        foreach ($routes as $route) {
            $round = $document->createElement('round');
            $round->setAttribute('title', (string) $route->name);
            $round->setAttribute('instructions', (string) $route->description);
            $round->setAttribute('legacy_route_id', (string) $route->id);
            $round->setAttribute('is_active', '1');
            $areaNode->appendChild($round);

            $this->appendCheckpoints($document, $round, (int) $route->id);
        }
    }

    private function appendCheckpoints(DOMDocument $document, DOMElement $roundNode, int $routeId): void
    {
        $zones = DB::connection('legacy')
            ->table('route_zones as rz')
            ->join('zones as z', 'z.id', '=', 'rz.zone_id')
            ->where('rz.route_id', $routeId)
            ->whereNull('z.deleted_at')
            ->orderBy('rz.order')
            ->orderBy('z.id')
            ->select([
                'z.id',
                'z.name',
                'z.address',
                'z.zone_id as qr_code',
                'rz.order',
            ])
            ->get();

        $position = 1;

        foreach ($zones as $zone) {
            $checkpoint = $document->createElement('checkpoint');
            $checkpoint->setAttribute('name', (string) $zone->name);
            $checkpoint->setAttribute('position', (string) $position);
            $checkpoint->setAttribute(
                'instructions',
                $this->normalizeAddress((string) ($zone->address ?? '')),
            );
            $checkpoint->setAttribute('legacy_zone_id', (string) $zone->id);
            $checkpoint->setAttribute('legacy_qr', (string) $zone->qr_code);
            $roundNode->appendChild($checkpoint);

            $this->appendQuestions($document, $checkpoint, (int) $zone->id);
            $position++;
        }
    }

    private function appendQuestions(DOMDocument $document, DOMElement $checkpointNode, int $zoneId): void
    {
        $questions = DB::connection('legacy')
            ->table('zone_questions')
            ->where('zone_id', $zoneId)
            ->orderBy('id')
            ->get();

        $questionPosition = 1;

        foreach ($questions as $question) {
            $questionNode = $document->createElement('question');
            $questionNode->setAttribute('body', (string) $question->question);
            $questionNode->setAttribute('position', (string) $questionPosition);
            $questionNode->setAttribute('legacy_id', (string) $question->id);
            $questionNode->setAttribute('guide', (string) ($question->guide ?? ''));
            $checkpointNode->appendChild($questionNode);

            $this->appendOptions($document, $questionNode, (int) $question->id);
            $questionPosition++;
        }
    }

    private function appendOptions(DOMDocument $document, DOMElement $questionNode, int $questionId): void
    {
        $answers = DB::connection('legacy')
            ->table('zone_question_answers')
            ->where('zone_question_id', $questionId)
            ->orderBy('severity')
            ->orderBy('id')
            ->get();

        $optionPosition = 1;

        foreach ($answers as $answer) {
            $option = $document->createElement('option');
            $option->setAttribute('label', (string) $answer->answer);
            $option->setAttribute('position', (string) $optionPosition);
            $option->setAttribute('legacy_severity', (string) $answer->severity);
            $option->setAttribute(
                'legacy_needs_report',
                (string) ((int) $answer->needs_report),
            );

            if ($answer->category_id !== null) {
                $option->setAttribute('legacy_category_id', (string) $answer->category_id);
            }

            $questionNode->appendChild($option);
            $optionPosition++;
        }
    }

    private function normalizeAddress(string $address): string
    {
        $trimmed = trim($address);

        if ($trimmed === '' || strcasecmp($trimmed, 'Sin dirección') === 0) {
            return '';
        }

        return $trimmed;
    }
}
