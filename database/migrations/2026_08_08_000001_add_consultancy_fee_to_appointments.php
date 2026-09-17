<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable: existing appointments won't have this populated. The
        // invoice view falls back to the doctor's current fee for those, so
        // nothing breaks — only newly-booked appointments get the accurate,
        // frozen-at-booking-time amount from here on.
        Schema::table('appointments', function (Blueprint $table) {
            $table->decimal('consultancy_fee', 8, 2)->nullable()->after('slot_end');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('consultancy_fee');
        });
    }
};
