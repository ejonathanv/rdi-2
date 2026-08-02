<?php

namespace App\Http\Controllers;

use App\Models\Checkpoint;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckpointQuestionnaireController extends Controller
{
    public function edit(Request $request, Checkpoint $checkpoint): Response
    {
        $checkpoint->loadMissing(['round.area', 'questions.options']);

        $this->authorize('update', $checkpoint->round);

        return Inertia::render('checkpoints/questionnaire', [
            'area' => $checkpoint->round->area->only(['id', 'name', 'code']),
            'round' => $checkpoint->round->only(['id', 'title']),
            'checkpoint' => [
                'id' => $checkpoint->id,
                'name' => $checkpoint->name,
                'instructions' => $checkpoint->instructions,
            ],
            'questions' => $checkpoint->questions->map(fn ($question) => [
                'id' => $question->id,
                'body' => $question->body,
                'position' => $question->position,
                'is_active' => $question->is_active,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'position' => $option->position,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }
}
