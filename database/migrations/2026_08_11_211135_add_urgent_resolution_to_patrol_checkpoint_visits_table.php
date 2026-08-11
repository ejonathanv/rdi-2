<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrol_checkpoint_visits', function (Blueprint $table) {
            $table->timestamp('urgent_resolved_at')->nullable()->after('urgent_notes');
            $table->foreignId('urgent_resolved_by_id')
                ->nullable()
                ->after('urgent_resolved_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patrol_checkpoint_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('urgent_resolved_by_id');
            $table->dropColumn('urgent_resolved_at');
        });
    }
};
