<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoint_submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkpoint_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkpoint_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkpoint_question_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['checkpoint_submission_id', 'checkpoint_question_id'],
                'checkpoint_submission_question_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_submission_answers');
    }
};
