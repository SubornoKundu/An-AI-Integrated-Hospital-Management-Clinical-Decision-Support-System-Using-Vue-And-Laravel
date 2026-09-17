<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // Filled only when a doctor uses "Override & Save Anyway" after
            // the AI safety check flagged something — a snapshot of exactly
            // what was flagged at that moment, so the record shows the
            // doctor was warned and chose to proceed, not that the system
            // silently missed something.
            $table->text('safety_check_override')->nullable()->after('instructions');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn('safety_check_override');
        });
    }
};
