<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patrol_runs', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique('pr_client_uuid_unique');
        });

        Schema::table('patrol_checkpoint_visits', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique('pcv_client_uuid_unique');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique('inc_client_uuid_unique');
            $table->timestamp('occurred_at')->nullable();
        });

        Schema::table('panic_alerts', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique('pa_client_uuid_unique');
            $table->timestamp('occurred_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panic_alerts', function (Blueprint $table) {
            $table->dropUnique('pa_client_uuid_unique');
            $table->dropColumn(['client_uuid', 'occurred_at']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropUnique('inc_client_uuid_unique');
            $table->dropColumn(['client_uuid', 'occurred_at']);
        });

        Schema::table('patrol_checkpoint_visits', function (Blueprint $table) {
            $table->dropUnique('pcv_client_uuid_unique');
            $table->dropColumn('client_uuid');
        });

        Schema::table('patrol_runs', function (Blueprint $table) {
            $table->dropUnique('pr_client_uuid_unique');
            $table->dropColumn('client_uuid');
        });
    }
};
