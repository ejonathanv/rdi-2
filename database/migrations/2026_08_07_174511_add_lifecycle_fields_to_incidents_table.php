<?php

use App\Enums\IncidentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('status')->default(IncidentStatus::Nueva->value)->after('is_urgent');
            $table->foreignId('assigned_to_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable()->after('assigned_to_id');
            $table->foreignId('resolved_by_id')->nullable()->after('acknowledged_at')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by_id');
            $table->text('resolution_notes')->nullable()->after('resolved_at');

            $table->index(['area_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['area_id', 'status']);
            $table->dropConstrainedForeignId('assigned_to_id');
            $table->dropConstrainedForeignId('resolved_by_id');
            $table->dropColumn([
                'status',
                'acknowledged_at',
                'resolved_at',
                'resolution_notes',
            ]);
        });
    }
};
