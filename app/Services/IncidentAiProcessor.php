<?php

namespace App\Services;

use App\Models\Area;
use App\Models\IncidentCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

class IncidentAiProcessor
{
    /**
     * @return array{
     *     cleaned_message: string,
     *     category_code: string|null,
     *     new_category: array{code: string, name: string, description: string|null}|null
     * }
     */
    public function process(string $rawMessage, Area $area): array
    {
        $fallback = [
            'cleaned_message' => trim($rawMessage),
            'category_code' => null,
            'new_category' => null,
        ];

        if (! config('openai.api_key')) {
            Log::warning('OPENAI_API_KEY no configurada; se omite categorización de incidencia.');

            return $fallback;
        }

        $categories = IncidentCategory::query()
            ->where('area_id', $area->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'code', 'description']);

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
            /** @var array{cleaned_message?: mixed, category_code?: mixed, new_category?: mixed} $decoded */
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

            $knownCodes = $categories->pluck('code')->all();

            if ($code !== null && ! in_array($code, $knownCodes, true)) {
                $code = null;
            }

            $newCategory = null;

            if ($code === null) {
                $newCategory = $this->normalizeNewCategory($decoded['new_category'] ?? null, $knownCodes);
            }

            return [
                'cleaned_message' => $cleaned !== '' ? $cleaned : trim($rawMessage),
                'category_code' => $code,
                'new_category' => $newCategory,
            ];
        } catch (Throwable $exception) {
            Log::error('Error al procesar incidencia con OpenAI.', [
                'area_id' => $area->id,
                'message' => $exception->getMessage(),
            ]);

            return $fallback;
        }
    }

    /**
     * @param  Collection<int, IncidentCategory>  $categories
     */
    private function systemPrompt(Collection $categories): string
    {
        if ($categories->isEmpty()) {
            $list = '(ninguna; debes proponer new_category)';
        } else {
            $list = $categories
                ->map(function (IncidentCategory $category) {
                    $line = "- {$category->code}: {$category->name}";

                    if ($category->description) {
                        $line .= " ({$category->description})";
                    }

                    return $line;
                })
                ->implode("\n");
        }

        return <<<PROMPT
Eres un asistente de seguridad industrial. Recibirás el reporte libre de un guardia.
Debes:
1) Reescribir el mensaje en español claro, profesional y conciso (sin inventar hechos).
2) Si alguna categoría de la lista encaja, devolver su category_code y new_category=null.
3) Si ninguna encaja (o la lista está vacía), category_code=null y proponer new_category reutilizable:
   - code: MAYUSCULAS_CON_GUION_BAJO, corto y estable (ej. FUGA_GAS)
   - name: nombre humano breve EN MAYÚSCULAS
   - description: una frase que ayude a clasificar reportes similares

Categorías disponibles:
{$list}

Responde SOLO JSON con esta forma:
{"cleaned_message":"...","category_code":"CODIGO_O_NULL","new_category":null}
o
{"cleaned_message":"...","category_code":null,"new_category":{"code":"NUEVO","name":"NOMBRE","description":"..."}}
PROMPT;
    }

    /**
     * @param  list<string>  $knownCodes
     * @return array{code: string, name: string, description: string|null}|null
     */
    private function normalizeNewCategory(mixed $payload, array $knownCodes): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $rawCode = is_string($payload['code'] ?? null) ? trim($payload['code']) : '';
        $name = is_string($payload['name'] ?? null) ? trim($payload['name']) : '';
        $description = is_string($payload['description'] ?? null)
            ? trim($payload['description'])
            : null;

        if ($rawCode === '' || $name === '') {
            return null;
        }

        $code = Str::upper(Str::slug($rawCode, '_'));
        $name = mb_strtoupper($name);

        if ($code === '' || strlen($code) > 100 || strlen($name) > 255) {
            return null;
        }

        if (in_array($code, $knownCodes, true)) {
            return null;
        }

        if ($description === '') {
            $description = null;
        }

        return [
            'code' => $code,
            'name' => $name,
            'description' => $description,
        ];
    }
}
