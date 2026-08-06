<?php

namespace App\Http\Requests\CheckpointScan;

use App\Concerns\UrgentVisitRules;
use App\Models\Checkpoint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCheckpointScanRequest extends FormRequest
{
    use UrgentVisitRules;

    private ?Checkpoint $checkpoint = null;

    public function authorize(): bool
    {
        $checkpoint = $this->checkpoint();

        return $this->user()?->canRespondToCheckpoint($checkpoint) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            ...$this->urgentVisitRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $checkpoint = $this->checkpoint();
            $questions = $checkpoint->questions()
                ->where('is_active', true)
                ->with('options')
                ->get()
                ->keyBy('id');

            /** @var array<string, int|string> $answers */
            $answers = $this->input('answers', []);

            foreach ($questions as $questionId => $question) {
                if (! array_key_exists((string) $questionId, $answers) && ! array_key_exists($questionId, $answers)) {
                    $validator->errors()->add(
                        "answers.{$questionId}",
                        __('Debes responder todas las preguntas.'),
                    );

                    continue;
                }

                $optionId = (int) ($answers[$questionId] ?? $answers[(string) $questionId]);
                $optionIds = $question->options->pluck('id')->all();

                if (! in_array($optionId, $optionIds, true)) {
                    $validator->errors()->add(
                        "answers.{$questionId}",
                        __('La opción seleccionada no es válida.'),
                    );
                }
            }

            foreach (array_keys($answers) as $questionId) {
                if (! $questions->has((int) $questionId)) {
                    $validator->errors()->add(
                        "answers.{$questionId}",
                        __('La pregunta no pertenece a este punto.'),
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => __('Debes responder el cuestionario.'),
            'photos.max' => __('Puedes adjuntar máximo :max fotos.'),
            'photos.*.image' => __('Cada archivo debe ser una imagen.'),
            'photos.*.mimes' => __('Las fotos deben ser JPEG, PNG o WebP.'),
            'photos.*.max' => __('Cada foto no puede superar los 10 MB.'),
            'photos.*.uploaded' => __('No se pudo subir la foto. Prueba con una imagen más pequeña.'),
            ...$this->urgentVisitMessages(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'photos' => __('fotos'),
            'photos.*' => __('foto'),
        ];
    }

    private function checkpoint(): Checkpoint
    {
        if ($this->checkpoint instanceof Checkpoint) {
            return $this->checkpoint;
        }

        $this->checkpoint = Checkpoint::query()
            ->where('token', $this->route('token'))
            ->where('is_active', true)
            ->with(['round.area'])
            ->firstOrFail();

        abort_unless($this->checkpoint->round->is_active, 404);

        return $this->checkpoint;
    }
}
