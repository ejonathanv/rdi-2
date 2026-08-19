<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrol_checkpoint_visit_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_checkpoint_visit_id')->constrained(indexName: 'pcvp_visit_id_fk')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['patrol_checkpoint_visit_id', 'position'], 'pcvp_visit_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_checkpoint_visit_photos');
    }
};
