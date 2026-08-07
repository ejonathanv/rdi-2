<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrol_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message_raw');
            $table->text('message_cleaned')->nullable();
            $table->foreignId('incident_category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_urgent')->default(false);
            $table->timestamp('categorized_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
