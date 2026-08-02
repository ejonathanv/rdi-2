<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckpointScan\StoreCheckpointScanRequest;
use App\Models\Checkpoint;
use App\Models\CheckpointSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckpointScanController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $checkpoint = $this->findActiveCheckpoint($token);

        abort_unless($request->user()?->canRespondToCheckpoint($checkpoint), 403);

        $questions = $checkpoint->questions()
            ->where('is_active', true)
            ->with(['options' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->get();

        return Inertia::render('checkpoints/scan', [
            'area' => $checkpoint->round->area->only(['id', 'name', 'code']),
            'round' => $checkpoint->round->only(['id', 'title']),
            'checkpoint' => [
                'id' => $checkpoint->id,
                'name' => $checkpoint->name,
                'instructions' => $checkpoint->instructions,
                'token' => $checkpoint->token,
            ],
            'questions' => $questions->map(fn ($question) => [
                'id' => $question->id,
                'body' => $question->body,
                'position' => $question->position,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'position' => $option->position,
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    public function store(StoreCheckpointScanRequest $request, string $token): RedirectResponse
    {
        $checkpoint = $this->findActiveCheckpoint($token);

        DB::transaction(function () use ($request, $checkpoint): void {
            $submission = CheckpointSubmission::query()->create([
                'checkpoint_id' => $checkpoint->id,
                'user_id' => $request->user()->id,
            ]);

            foreach ($request->validated('answers') as $questionId => $optionId) {
                $submission->answers()->create([
                    'checkpoint_question_id' => (int) $questionId,
                    'checkpoint_question_option_id' => (int) $optionId,
                ]);
            }
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Respuestas enviadas correctamente.'),
        ]);

        return to_route('checkpoints.scan.complete', $token);
    }

    public function complete(Request $request, string $token): Response
    {
        $checkpoint = $this->findActiveCheckpoint($token);

        abort_unless($request->user()?->canRespondToCheckpoint($checkpoint), 403);

        return Inertia::render('checkpoints/scan-complete', [
            'checkpoint' => [
                'name' => $checkpoint->name,
                'token' => $checkpoint->token,
            ],
            'round' => $checkpoint->round->only(['title']),
        ]);
    }

    private function findActiveCheckpoint(string $token): Checkpoint
    {
        $checkpoint = Checkpoint::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->with(['round.area'])
            ->firstOrFail();

        abort_unless($checkpoint->round->is_active, 404);

        return $checkpoint;
    }
}
