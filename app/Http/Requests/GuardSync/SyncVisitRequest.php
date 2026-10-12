<?php

namespace App\Http\Requests\GuardSync;

use App\Concerns\OfflineSyncRules;
use App\Concerns\UrgentVisitRules;
use App\Enums\PatrolVisitOutcome;
use App\Models\Checkpoint;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncVisitRequest extends FormRequest
{
    use OfflineSyncRules;
    use UrgentVisitRules;

    private ?PatrolRun $patrol = null;

    private ?Checkpoint $checkpoint = null;

    public function authorize(): bool
    {
        return $this->user()?->hasGuardRole() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_uuid' => ['required', 'uuid'],
            'patrol_client_uuid' => ['required', 'uuid'],
            'checkpoint_token' => ['required', 'string'],
            'outcome' => ['required', Rule::in([PatrolVisitOutcome::Questionnaire->value, PatrolVisitOutcome::AllClear->value])],
            'answers' => ['nullable', 'array', 'required_if:outcome,'.PatrolVisitOutcome::Questionnaire->value],
            'answers.*' => ['required', 'integer'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'reviewed_at' => $this->clientTimestampRules(),
            ...$this->urgentVisitRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->existingVisit()) {
                return;
            }

            $patrol = $this->patrol();

            $checkpoint = Checkpoint::query()
                ->matchingSyncToken((string) $this->input('checkpoint_token'), $this->reviewedAt())
                ->where('round_id', $patrol->round_id)
                ->first();

            if (! $checkpoint) {
                $validator->errors()->add('checkpoint_token', __('El QR no es válido para este recorrido o fue regenerado.'));

                return;
            }

            $this->checkpoint = $checkpoint;

            if (! $patrol->isInProgress() || $patrol->visits()->where('checkpoint_id', $checkpoint->id)->exists()) {
                $validator->errors()->add('checkpoint_token', __('Este punto ya fue revisado en el recorrido actual.'));

                return;
            }

            if ($this->input('outcome') === PatrolVisitOutcome::Questionnaire->value) {
                $this->validateAnswers($validator, $checkpoint);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required_if' => __('Debes responder el cuestionario.'),
            'photos.max' => __('Puedes adjuntar máximo :max fotos.'),
            ...$this->urgentVisitMessages(),
            ...$this->clientTimestampMessages('reviewed_at'),
        ];
    }

    public function existingVisit(): ?PatrolCheckpointVisit
    {
        return PatrolCheckpointVisit::query()
            ->where('client_uuid', (string) $this->input('client_uuid'))
            ->whereHas('patrolRun', fn ($query) => $query->where('user_id', $this->user()->id))
            ->first();
    }

    public function patrol(): PatrolRun
    {
        return $this->patrol ??= $this->ownPatrolOrConflict((string) $this->input('patrol_client_uuid'));
    }

    public function checkpoint(): Checkpoint
    {
        return $this->checkpoint ?? throw new \LogicException('Checkpoint not resolved.');
    }

    public function outcome(): PatrolVisitOutcome
    {
        return PatrolVisitOutcome::from((string) $this->input('outcome'));
    }

    public function reviewedAt(): CarbonInterface
    {
        return $this->clientTimestamp('reviewed_at');
    }

    private function validateAnswers(Validator $validator, Checkpoint $checkpoint): void
    {
        $questions = $checkpoint->questions()->with('options')->get()->keyBy('id');

        /** @var array<string, int|string> $answers */
        $answers = $this->input('answers', []);

        foreach ($answers as $questionId => $optionId) {
            $question = $questions->get((int) $questionId);

            if (! $question) {
                $validator->errors()->add("answers.{$questionId}", __('La pregunta no pertenece a este punto.'));

                continue;
            }

            if (! $question->options->contains('id', (int) $optionId)) {
                $validator->errors()->add("answers.{$questionId}", __('La opción seleccionada no es válida.'));
            }
        }
    }
}
