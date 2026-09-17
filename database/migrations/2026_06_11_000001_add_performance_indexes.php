<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Faster filtering by date, status, and combined queries
            $table->index('appointment_date', 'idx_appt_date');
            $table->index('status',           'idx_appt_status');
            $table->index(['doctor_id',  'appointment_date'], 'idx_appt_doctor_date');
            $table->index(['patient_id', 'appointment_date'], 'idx_appt_patient_date');
            $table->index(['patient_id', 'status'],           'idx_appt_patient_status');
        });

        Schema::table('doctors', function (Blueprint $table) {
            // Faster doctor search/filter
            $table->index('is_active', 'idx_doctor_active');
        });

        Schema::table('patients', function (Blueprint $table) {
            // Faster patient lookup
            $table->index('email', 'idx_patient_email');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appt_date');
            $table->dropIndex('idx_appt_status');
            $table->dropIndex('idx_appt_doctor_date');
            $table->dropIndex('idx_appt_patient_date');
            $table->dropIndex('idx_appt_patient_status');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropIndex('idx_doctor_active');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('idx_patient_email');
        });
    }
};
