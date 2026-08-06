<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrol_checkpoint_visits', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->after('outcome');
            $table->text('urgent_notes')->nullable()->after('is_urgent');
        });
    }

    public function down(): void
    {
        Schema::table('patrol_checkpoint_visits', function (Blueprint $table) {
            $table->dropColumn(['is_urgent', 'urgent_notes']);
        });
    }
};
