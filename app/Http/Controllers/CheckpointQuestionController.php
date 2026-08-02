<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckpointQuestion\ReorderCheckpointQuestionsRequest;
use App\Http\Requests\CheckpointQuestion\StoreCheckpointQuestionRequest;
use App\Http\Requests\CheckpointQuestion\UpdateCheckpointQuestionRequest;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckpointQuestionController extends Controller
{
    public function store(StoreCheckpointQuestionRequest $request, Checkpoint $checkpoint): RedirectResponse
    {
        DB::transaction(function () use ($request, $checkpoint): void {
            $position = ((int) $checkpoint->questions()->max('position')) + 1;

            $question = $checkpoint->questions()->create([
                'body' => $request->validated('body'),
                'is_active' => $request->boolean('is_active', true),
                'position' => $position,
            ]);

            foreach (array_values($request->validated('options')) as $index => $label) {
                $question->options()->create([
                    'label' => $label,
                    'position' => $index + 1,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pregunta agregada al cuestionario.')]);

        return to_route('checkpoints.questionnaire.edit', $checkpoint);
    }

    public function update(UpdateCheckpointQuestionRequest $request, CheckpointQuestion $question): RedirectResponse
    {
        $question->loadMissing('checkpoint');

        DB::transaction(function () use ($request, $question): void {
            $question->update([
                'body' => $request->validated('body'),
                'is_active' => $request->boolean('is_active'),
            ]);

            $question->options()->delete();

            foreach (array_values($request->validated('options')) as $index => $label) {
                $question->options()->create([
                    'label' => $label,
                    'position' => $index + 1,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pregunta actualizada.')]);

        return to_route('checkpoints.questionnaire.edit', $question->checkpoint);
    }

    public function destroy(CheckpointQuestion $question): RedirectResponse
    {
        $question->loadMissing('checkpoint.round.area');

        $this->authorize('update', $question->checkpoint->round);

        $checkpoint = $question->checkpoint;
        $question->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pregunta eliminada.')]);

        return to_route('checkpoints.questionnaire.edit', $checkpoint);
    }

    public function reorder(ReorderCheckpointQuestionsRequest $request, Checkpoint $checkpoint): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('order') as $position => $questionId) {
                CheckpointQuestion::query()
                    ->whereKey($questionId)
                    ->update(['position' => $position + 1]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Orden de preguntas actualizado.')]);

        return to_route('checkpoints.questionnaire.edit', $checkpoint);
    }
}
