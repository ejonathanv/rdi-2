<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoint_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkpoint_question_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_question_options');
    }
};
