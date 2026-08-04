<?php

use App\Enums\PatrolVisitOutcome;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrol_checkpoint_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->constrained()->cascadeOnDelete();
            $table->timestamp('reviewed_at');
            $table->string('outcome')->default(PatrolVisitOutcome::Questionnaire->value);
            $table->foreignId('checkpoint_submission_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['patrol_run_id', 'checkpoint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_checkpoint_visits');
    }
};
