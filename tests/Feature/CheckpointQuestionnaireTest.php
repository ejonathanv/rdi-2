<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckpointQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_questionnaire_configuration(): void
    {
        [$admin, $checkpoint] = $this->adminWithCheckpoint();

        $this->actingAs($admin)
            ->get(route('checkpoints.questionnaire.edit', $checkpoint))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('checkpoints/questionnaire')
                ->where('checkpoint.id', $checkpoint->id)
                ->has('questions', 0));
    }

    public function test_admin_can_add_question_with_options(): void
    {
        [$admin, $checkpoint] = $this->adminWithCheckpoint();

        $this->actingAs($admin)
            ->post(route('checkpoints.questions.store', $checkpoint), [
                'body' => '¿Está el candado de la puerta bien cerrado?',
                'is_active' => true,
                'options' => ['Sí', 'No', 'No sé'],
            ])
            ->assertRedirect(route('checkpoints.questionnaire.edit', $checkpoint));

        $question = $checkpoint->questions()->with('options')->first();

        $this->assertNotNull($question);
        $this->assertSame('¿Está el candado de la puerta bien cerrado?', $question->body);
        $this->assertSame(['Sí', 'No', 'No sé'], $question->options->pluck('label')->all());
    }

    public function test_question_requires_at_least_two_options(): void
    {
        [$admin, $checkpoint] = $this->adminWithCheckpoint();

        $this->actingAs($admin)
            ->post(route('checkpoints.questions.store', $checkpoint), [
                'body' => '¿Todo en orden?',
                'options' => ['Sí'],
            ])
            ->assertSessionHasErrors('options');

        $this->assertDatabaseCount('checkpoint_questions', 0);
    }

    public function test_admin_can_update_and_delete_question(): void
    {
        [$admin, $checkpoint] = $this->adminWithCheckpoint();
        $question = CheckpointQuestion::factory()->create([
            'checkpoint_id' => $checkpoint->id,
            'body' => 'Pregunta original',
            'position' => 1,
        ]);
        $question->options()->createMany([
            ['label' => 'Sí', 'position' => 1],
            ['label' => 'No', 'position' => 2],
        ]);

        $this->actingAs($admin)
            ->put(route('questions.update', $question), [
                'body' => '¿Candado cerrado?',
                'is_active' => true,
                'options' => ['Sí', 'No', 'Tal vez'],
            ])
            ->assertRedirect(route('checkpoints.questionnaire.edit', $checkpoint));

        $question->refresh()->load('options');
        $this->assertSame('¿Candado cerrado?', $question->body);
        $this->assertSame(['Sí', 'No', 'Tal vez'], $question->options->pluck('label')->all());

        $this->actingAs($admin)
            ->delete(route('questions.destroy', $question))
            ->assertRedirect(route('checkpoints.questionnaire.edit', $checkpoint));

        $this->assertDatabaseMissing('checkpoint_questions', ['id' => $question->id]);
    }

    public function test_admin_can_reorder_questions(): void
    {
        [$admin, $checkpoint] = $this->adminWithCheckpoint();
        $first = CheckpointQuestion::factory()->create([
            'checkpoint_id' => $checkpoint->id,
            'body' => 'Primera',
            'position' => 1,
        ]);
        $second = CheckpointQuestion::factory()->create([
            'checkpoint_id' => $checkpoint->id,
            'body' => 'Segunda',
            'position' => 2,
        ]);

        $this->actingAs($admin)
            ->put(route('checkpoints.questions.reorder', $checkpoint), [
                'order' => [$second->id, $first->id],
            ])
            ->assertRedirect(route('checkpoints.questionnaire.edit', $checkpoint));

        $this->assertSame(
            ['Segunda', 'Primera'],
            $checkpoint->questions()->orderBy('position')->pluck('body')->all(),
        );
    }

    public function test_guard_cannot_configure_questionnaire(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        $this->actingAs($guard)
            ->get(route('checkpoints.questionnaire.edit', $checkpoint))
            ->assertForbidden();
    }

    public function test_admin_cannot_configure_questionnaire_of_another_area(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);

        $otherArea = Area::factory()->create();
        $round = Round::factory()->create(['area_id' => $otherArea->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        $this->actingAs($admin)
            ->get(route('checkpoints.questionnaire.edit', $checkpoint))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Checkpoint}
     */
    private function adminWithCheckpoint(): array
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $round = Round::factory()->create(['area_id' => $area->id]);
        $checkpoint = Checkpoint::factory()->create(['round_id' => $round->id]);

        return [$admin, $checkpoint];
    }
}
