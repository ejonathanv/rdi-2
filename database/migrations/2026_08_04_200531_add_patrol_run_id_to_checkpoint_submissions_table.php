<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkpoint_submissions', function (Blueprint $table) {
            $table->foreignId('patrol_run_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checkpoint_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patrol_run_id');
        });
    }
};
