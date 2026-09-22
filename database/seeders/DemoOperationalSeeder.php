<?php

namespace Database\Seeders;

use App\Enums\IncidentStatus;
use App\Enums\PatrolRunStatus;
use App\Enums\PatrolVisitOutcome;
use App\Models\Area;
use App\Models\Checkpoint;
use App\Models\CheckpointQuestion;
use App\Models\CheckpointSubmission;
use App\Models\CheckpointSubmissionAnswer;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\PanicAlert;
use App\Models\PatrolCheckpointVisit;
use App\Models\PatrolRun;
use App\Models\Round;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoOperationalSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const array DefaultOptions = ['Sí', 'No'];

    public function run(): void
    {
        $area = Area::query()->where('code', 'PLANTA-NORTE')->firstOrFail();

        $guards = User::query()
            ->whereIn('email', ['guard@example.com', 'guard2@example.com'])
            ->get();
        $contacts = User::query()
            ->whereIn('email', ['contact@example.com', 'contact2@example.com'])
            ->get();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $categories = IncidentCategory::query()
            ->where('area_id', $area->id)
            ->get();

        foreach ($categories->take(6) as $category) {
            $category->contacts()->syncWithoutDetaching($contacts->pluck('id')->all());
        }

        $rounds = $this->seedRounds($area, $contacts);

        $this->seedPatrolActivity($rounds, $guards, $contacts->first() ?? $admin, $categories);
        $this->seedStandaloneIncidents($area, $guards, $contacts, $admin, $categories, $rounds);
        $this->seedPanicAlerts($area, $guards, $rounds);
    }

    /**
     * @param  Collection<int, User>  $contacts
     * @return Collection<int, Round>
     */
    private function seedRounds(Area $area, Collection $contacts): Collection
    {
        $definitions = [
            [
                'title' => 'Recorrido perimetral',
                'instructions' => 'Recorrer el perímetro verificando accesos, cercas e iluminación.',
                'checkpoints' => [
                    ['name' => 'Entrada principal', 'instructions' => 'Verificar portón, iluminación y bitácora de acceso.'],
                    ['name' => 'Cerca norte', 'instructions' => 'Revisar malla perimetral y sensores.'],
                    ['name' => 'Caseta de vigilancia', 'instructions' => 'Confirmar presencia y radio operativo.'],
                    ['name' => 'Estacionamiento exterior', 'instructions' => 'Detectar vehículos no autorizados.'],
                    ['name' => 'Salida de emergencia E1', 'instructions' => 'Puerta libre de obstáculos y señalización visible.'],
                ],
            ],
            [
                'title' => 'Recorrido áreas comunes',
                'instructions' => 'Revisión de zonas compartidas de colaboradores.',
                'checkpoints' => [
                    ['name' => 'Lobby', 'instructions' => 'Accesos peatonales y torniquetes.'],
                    ['name' => 'Comedor', 'instructions' => 'Orden, salidas y condiciones generales.'],
                    ['name' => 'Pasillo RH', 'instructions' => 'Pasillos libres de obstrucciones.'],
                    ['name' => 'Gimnasio', 'instructions' => 'Daños o condiciones anómalas.'],
                ],
            ],
            [
                'title' => 'Recorrido de producción',
                'instructions' => 'Abarca piso de producción y mezanine.',
                'checkpoints' => [
                    ['name' => 'Línea A', 'instructions' => 'Condiciones de seguridad en línea.'],
                    ['name' => 'Línea B', 'instructions' => 'Bloqueos y EPP visible.'],
                    ['name' => 'Almacén de insumos', 'instructions' => 'Candados, sellos y manipulación.'],
                    ['name' => 'Cuarto de compresores', 'instructions' => 'Ruidos anómalos y fugas.'],
                    ['name' => 'Mezanine este', 'instructions' => 'Barandales y tránsito.'],
                    ['name' => 'Mezanine oeste', 'instructions' => 'Iluminación y orden.'],
                    ['name' => 'Andén de carga', 'instructions' => 'Muelles y sellos de trailers.'],
                    ['name' => 'Área de scrap', 'instructions' => 'Contención y señalización.'],
                ],
            ],
            [
                'title' => 'Recorrido área administrativa',
                'instructions' => 'Zonas de oficinas y acceso de personal administrativo.',
                'checkpoints' => [
                    ['name' => 'Recepción oficinas', 'instructions' => 'Control de visitas y bitácora.'],
                    ['name' => 'Sala de juntas', 'instructions' => 'Cierre de puertas y equipo.'],
                    ['name' => 'Archivo', 'instructions' => 'Acceso restringido y candados.'],
                ],
            ],
            [
                'title' => 'Recorrido puertas de emergencia',
                'instructions' => 'Verificación de las puertas de emergencia de la planta.',
                'checkpoints' => [
                    ['name' => 'Puerta emerg. 1', 'instructions' => 'Apertura libre y barra antipánico.'],
                    ['name' => 'Puerta emerg. 2', 'instructions' => 'Señalización e iluminación de emergencia.'],
                    ['name' => 'Puerta emerg. 3', 'instructions' => 'Sin bloqueos ni material apilado.'],
                    ['name' => 'Puerta emerg. 4', 'instructions' => 'Cierre correcto tras prueba.'],
                    ['name' => 'Puerta emerg. 5', 'instructions' => 'Estado de chapas y sellos.'],
                    ['name' => 'Puerta emerg. 6', 'instructions' => 'Ruta evacuación despejada.'],
                ],
            ],
        ];

        $rounds = collect();

        foreach ($definitions as $definition) {
            $round = Round::query()->create([
                'area_id' => $area->id,
                'title' => $definition['title'],
                'instructions' => $definition['instructions'],
                'is_active' => true,
            ]);

            if ($contacts->isNotEmpty()) {
                $round->contacts()->attach($contacts->pluck('id')->all());
            }

            foreach ($definition['checkpoints'] as $index => $checkpointData) {
                $checkpoint = $round->checkpoints()->create([
                    'name' => $checkpointData['name'],
                    'instructions' => $checkpointData['instructions'],
                    'position' => $index + 1,
                    'token' => (string) Str::uuid(),
                    'is_active' => true,
                ]);

                $this->seedQuestionnaire($checkpoint, $checkpointData['name']);
            }

            $rounds->push($round->load('checkpoints.questions.options'));
        }

        return $rounds;
    }

    private function seedQuestionnaire(Checkpoint $checkpoint, string $checkpointName): void
    {
        $questions = match (true) {
            str_contains(mb_strtolower($checkpointName), 'almacén'),
            str_contains(mb_strtolower($checkpointName), 'archivo') => [
                '¿Están los accesos asegurados?',
                '¿Se observan evidencias de manipulación?',
            ],
            str_contains(mb_strtolower($checkpointName), 'puerta') => [
                '¿La puerta abre sin obstrucciones?',
                '¿La señalización es visible?',
            ],
            default => [
                '¿Se observan daños o condiciones inseguras?',
                '¿El área está libre de anomalías?',
            ],
        };

        foreach ($questions as $position => $body) {
            $question = $checkpoint->questions()->create([
                'body' => $body,
                'position' => $position + 1,
                'is_active' => true,
            ]);

            foreach (self::DefaultOptions as $optionPosition => $label) {
                $question->options()->create([
                    'label' => $label,
                    'position' => $optionPosition + 1,
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Round>  $rounds
     * @param  Collection<int, User>  $guards
     * @param  Collection<int, IncidentCategory>  $categories
     */
    private function seedPatrolActivity(
        Collection $rounds,
        Collection $guards,
        User $resolver,
        Collection $categories,
    ): void {
        if ($guards->isEmpty()) {
            return;
        }

        foreach ($rounds as $roundIndex => $round) {
            $completedCount = 2 + ($roundIndex % 2);

            for ($i = 0; $i < $completedCount; $i++) {
                $guard = $guards[$i % $guards->count()];
                $startedAt = now()->subDays(6 - $roundIndex)->subHours(2 + $i)->subMinutes($i * 17);

                $this->createCompletedPatrol(
                    round: $round,
                    guard: $guard,
                    resolver: $resolver,
                    categories: $categories,
                    startedAt: $startedAt,
                    withIncident: $i === 0 && $roundIndex < 3,
                    withUrgent: $i === 1 && $roundIndex === 0,
                );
            }
        }

        $activeRound = $rounds->first();
        $secondRound = $rounds->skip(1)->first() ?? $activeRound;

        $this->createInProgressPatrol(
            round: $activeRound,
            guard: $guards->first(),
            visitedCount: max(1, (int) floor($activeRound->checkpoints->count() / 2)),
        );

        if ($guards->count() > 1 && $secondRound !== null) {
            $this->createInProgressPatrol(
                round: $secondRound,
                guard: $guards->last(),
                visitedCount: 1,
            );
        }
    }

    /**
     * @param  Collection<int, IncidentCategory>  $categories
     */
    private function createCompletedPatrol(
        Round $round,
        User $guard,
        User $resolver,
        Collection $categories,
        CarbonInterface $startedAt,
        bool $withIncident,
        bool $withUrgent,
    ): PatrolRun {
        $finishedAt = $startedAt->copy()->addMinutes(35 + $round->checkpoints->count() * 4);

        $patrol = PatrolRun::query()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::Completed,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ]);

        $incidentCreated = false;

        foreach ($round->checkpoints as $index => $checkpoint) {
            $reviewedAt = $startedAt->copy()->addMinutes(5 + ($index * 4));
            $useQuestionnaire = $index % 2 === 0;
            $createIncidentHere = $withIncident && ! $incidentCreated && $index === min(2, $round->checkpoints->count() - 1);
            $markUrgent = $withUrgent && $index === 0;

            $submissionId = null;
            $outcome = PatrolVisitOutcome::AllClear;

            if ($createIncidentHere) {
                $outcome = PatrolVisitOutcome::Incident;
                $this->createIncidentForVisit(
                    areaId: $round->area_id,
                    guard: $guard,
                    patrol: $patrol,
                    checkpoint: $checkpoint,
                    category: $categories->random(),
                    reviewedAt: $reviewedAt,
                    status: IncidentStatus::EnAtencion,
                    assignedTo: $resolver,
                );
                $incidentCreated = true;
            } elseif ($useQuestionnaire && $checkpoint->questions->isNotEmpty()) {
                $outcome = PatrolVisitOutcome::Questionnaire;
                $submission = $this->createSubmissionWithAnswers($checkpoint, $guard, $patrol, $reviewedAt);
                $submissionId = $submission->id;
            }

            PatrolCheckpointVisit::query()->create([
                'patrol_run_id' => $patrol->id,
                'checkpoint_id' => $checkpoint->id,
                'reviewed_at' => $reviewedAt,
                'outcome' => $outcome,
                'checkpoint_submission_id' => $submissionId,
                'is_urgent' => $markUrgent,
                'urgent_notes' => $markUrgent ? 'Se requiere revisión inmediata del punto.' : null,
                'urgent_resolved_at' => $markUrgent ? $reviewedAt->copy()->addHour() : null,
                'urgent_resolved_by_id' => $markUrgent ? $resolver->id : null,
            ]);
        }

        return $patrol;
    }

    private function createInProgressPatrol(Round $round, User $guard, int $visitedCount): PatrolRun
    {
        $startedAt = now()->subMinutes(25);

        $patrol = PatrolRun::query()->create([
            'user_id' => $guard->id,
            'round_id' => $round->id,
            'status' => PatrolRunStatus::InProgress,
            'started_at' => $startedAt,
            'finished_at' => null,
        ]);

        $checkpoints = $round->checkpoints->take($visitedCount);

        foreach ($checkpoints as $index => $checkpoint) {
            $reviewedAt = $startedAt->copy()->addMinutes(4 + ($index * 5));
            $submissionId = null;
            $outcome = PatrolVisitOutcome::AllClear;

            if ($checkpoint->questions->isNotEmpty() && $index === 0) {
                $outcome = PatrolVisitOutcome::Questionnaire;
                $submission = $this->createSubmissionWithAnswers($checkpoint, $guard, $patrol, $reviewedAt);
                $submissionId = $submission->id;
            }

            PatrolCheckpointVisit::query()->create([
                'patrol_run_id' => $patrol->id,
                'checkpoint_id' => $checkpoint->id,
                'reviewed_at' => $reviewedAt,
                'outcome' => $outcome,
                'checkpoint_submission_id' => $submissionId,
                'is_urgent' => $index === 0 && $round->title === 'Recorrido perimetral',
                'urgent_notes' => $index === 0 && $round->title === 'Recorrido perimetral'
                    ? 'Urgente abierto durante rondín en curso.'
                    : null,
            ]);
        }

        return $patrol;
    }

    private function createSubmissionWithAnswers(
        Checkpoint $checkpoint,
        User $guard,
        PatrolRun $patrol,
        CarbonInterface $reviewedAt,
    ): CheckpointSubmission {
        $submission = CheckpointSubmission::query()->create([
            'checkpoint_id' => $checkpoint->id,
            'user_id' => $guard->id,
            'patrol_run_id' => $patrol->id,
            'created_at' => $reviewedAt,
            'updated_at' => $reviewedAt,
        ]);

        foreach ($checkpoint->questions as $question) {
            /** @var CheckpointQuestion $question */
            $option = $question->options->first();

            if ($option === null) {
                continue;
            }

            CheckpointSubmissionAnswer::query()->create([
                'checkpoint_submission_id' => $submission->id,
                'checkpoint_question_id' => $question->id,
                'checkpoint_question_option_id' => $option->id,
            ]);
        }

        return $submission;
    }

    private function createIncidentForVisit(
        int $areaId,
        User $guard,
        PatrolRun $patrol,
        Checkpoint $checkpoint,
        IncidentCategory $category,
        CarbonInterface $reviewedAt,
        IncidentStatus $status,
        ?User $assignedTo = null,
    ): Incident {
        $isUrgent = fake()->boolean(40);

        return Incident::query()->create([
            'area_id' => $areaId,
            'user_id' => $guard->id,
            'patrol_run_id' => $patrol->id,
            'checkpoint_id' => $checkpoint->id,
            'message_raw' => "Hallazgo en {$checkpoint->name}: se detectó una condición que requiere seguimiento.",
            'message_cleaned' => "Condición reportada en {$checkpoint->name} durante el rondín.",
            'incident_category_id' => $category->id,
            'is_urgent' => $isUrgent,
            'status' => $status,
            'assigned_to_id' => $assignedTo?->id,
            'acknowledged_at' => $status !== IncidentStatus::Nueva ? $reviewedAt->copy()->addMinutes(20) : null,
            'resolved_by_id' => $status->isTerminal() ? $assignedTo?->id : null,
            'resolved_at' => $status->isTerminal() ? $reviewedAt->copy()->addHours(2) : null,
            'resolution_notes' => $status->isTerminal() ? 'Atendido en demo de datos.' : null,
            'categorized_at' => $reviewedAt,
            'created_at' => $reviewedAt,
            'updated_at' => $reviewedAt,
        ]);
    }

    /**
     * @param  Collection<int, User>  $guards
     * @param  Collection<int, User>  $contacts
     * @param  Collection<int, IncidentCategory>  $categories
     * @param  Collection<int, Round>  $rounds
     */
    private function seedStandaloneIncidents(
        Area $area,
        Collection $guards,
        Collection $contacts,
        User $admin,
        Collection $categories,
        Collection $rounds,
    ): void {
        if ($guards->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $assignee = $contacts->first() ?? $admin;
        $checkpoint = $rounds->first()?->checkpoints->first();

        $samples = [
            [
                'status' => IncidentStatus::Nueva,
                'urgent' => true,
                'hoursAgo' => 2,
                'message' => 'Se observó acceso sin registro en la entrada de personal.',
            ],
            [
                'status' => IncidentStatus::Nueva,
                'urgent' => false,
                'hoursAgo' => 5,
                'message' => 'Falta de señalización temporal en pasillo de oficinas.',
            ],
            [
                'status' => IncidentStatus::EnAtencion,
                'urgent' => true,
                'hoursAgo' => 8,
                'message' => 'Derrame menor de lubricante cerca del andén.',
            ],
            [
                'status' => IncidentStatus::Resuelta,
                'urgent' => false,
                'hoursAgo' => 30,
                'message' => 'Iluminación defectuosa en estacionamiento exterior, ya reemplazada.',
            ],
            [
                'status' => IncidentStatus::Resuelta,
                'urgent' => true,
                'hoursAgo' => 48,
                'message' => 'Puerta de emergencia con cierre defectuoso, se corrigió el mecanismo.',
            ],
            [
                'status' => IncidentStatus::Descartada,
                'urgent' => false,
                'hoursAgo' => 72,
                'message' => 'Reporte duplicado de condición ya atendida el día anterior.',
            ],
        ];

        foreach ($samples as $index => $sample) {
            $createdAt = now()->subHours($sample['hoursAgo']);
            $status = $sample['status'];
            $guard = $guards[$index % $guards->count()];
            $category = $categories[$index % $categories->count()];

            Incident::query()->create([
                'area_id' => $area->id,
                'user_id' => $guard->id,
                'patrol_run_id' => null,
                'checkpoint_id' => $index % 2 === 0 ? $checkpoint?->id : null,
                'message_raw' => $sample['message'],
                'message_cleaned' => $sample['message'],
                'incident_category_id' => $category->id,
                'is_urgent' => $sample['urgent'],
                'status' => $status,
                'assigned_to_id' => in_array($status, [IncidentStatus::EnAtencion, IncidentStatus::Resuelta, IncidentStatus::Descartada], true)
                    ? $assignee->id
                    : null,
                'acknowledged_at' => $status !== IncidentStatus::Nueva
                    ? $createdAt->copy()->addMinutes(25)
                    : null,
                'resolved_by_id' => $status->isTerminal() ? $assignee->id : null,
                'resolved_at' => $status->isTerminal() ? $createdAt->copy()->addHours(3) : null,
                'resolution_notes' => $status === IncidentStatus::Resuelta
                    ? 'Cerrada en datos de demostración.'
                    : ($status === IncidentStatus::Descartada ? 'Descartada: duplicado.' : null),
                'categorized_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $guards
     * @param  Collection<int, Round>  $rounds
     */
    private function seedPanicAlerts(Area $area, Collection $guards, Collection $rounds): void
    {
        if ($guards->isEmpty()) {
            return;
        }

        $activePatrol = PatrolRun::query()
            ->where('status', PatrolRunStatus::InProgress)
            ->whereIn('round_id', $rounds->pluck('id'))
            ->first();

        PanicAlert::query()->create([
            'area_id' => $area->id,
            'user_id' => $guards->first()->id,
            'patrol_run_id' => $activePatrol?->id,
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        PanicAlert::query()->create([
            'area_id' => $area->id,
            'user_id' => $guards->last()->id,
            'patrol_run_id' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }
}
