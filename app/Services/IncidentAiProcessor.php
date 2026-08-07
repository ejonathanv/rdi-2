<?php

namespace App\Services;

use App\Models\Area;
use App\Models\IncidentCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

class IncidentAiProcessor
{
    /**
     * @return array{cleaned_message: string, category_code: string|null}
     */
    public function process(string $rawMessage, Area $area): array
    {
        $categories = IncidentCategory::query()
            ->where('area_id', $area->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'code', 'description']);

        if ($categories->isEmpty()) {
            return [
                'cleaned_message' => trim($rawMessage),
                'category_code' => null,
            ];
        }

        if (! config('openai.api_key')) {
            Log::warning('OPENAI_API_KEY no configurada; se omite categorización de incidencia.');

            return [
                'cleaned_message' => trim($rawMessage),
                'category_code' => null,
            ];
        }

        try {
            $response = OpenAI::chat()->create([
                'model' => config('openai.model', 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt($categories),
                    ],
                    [
                        'role' => 'user',
                        'content' => $rawMessage,
                    ],
                ],
            ]);

            $content = $response->choices[0]->message->content ?? '{}';
            /** @var array{cleaned_message?: mixed, category_code?: mixed} $decoded */
            $decoded = json_decode($content, true) ?: [];

            $cleaned = is_string($decoded['cleaned_message'] ?? null)
                ? trim($decoded['cleaned_message'])
                : trim($rawMessage);

            $code = is_string($decoded['category_code'] ?? null)
                ? strtoupper(trim($decoded['category_code']))
                : null;

            if ($code === '' || $code === 'NULL') {
                $code = null;
            }

            if ($code !== null && ! $categories->contains(fn (IncidentCategory $category) => $category->code === $code)) {
                $code = null;
            }

            return [
                'cleaned_message' => $cleaned !== '' ? $cleaned : trim($rawMessage),
                'category_code' => $code,
            ];
        } catch (Throwable $exception) {
            Log::error('Error al procesar incidencia con OpenAI.', [
                'area_id' => $area->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'cleaned_message' => trim($rawMessage),
                'category_code' => null,
            ];
        }
    }

    /**
     * @param  Collection<int, IncidentCategory>  $categories
     */
    private function systemPrompt(Collection $categories): string
    {
        $list = $categories
            ->map(function (IncidentCategory $category) {
                $line = "- {$category->code}: {$category->name}";

                if ($category->description) {
                    $line .= " ({$category->description})";
                }

                return $line;
            })
            ->implode("\n");

        return <<<PROMPT
Eres un asistente de seguridad industrial. Recibirás el reporte libre de un guardia.
Debes:
1) Reescribir el mensaje en español claro, profesional y conciso (sin inventar hechos).
2) Elegir exactamente un category_code de la lista, o null si ninguno encaja.

Categorías disponibles:
{$list}

Responde SOLO JSON con esta forma:
{"cleaned_message":"...","category_code":"CODIGO_O_NULL"}
PROMPT;
    }
}
