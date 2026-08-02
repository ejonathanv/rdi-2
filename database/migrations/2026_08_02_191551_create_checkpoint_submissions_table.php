<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoint_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkpoint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['checkpoint_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoint_submissions');
    }
};
