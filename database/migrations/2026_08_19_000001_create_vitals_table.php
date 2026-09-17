<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One vitals record per appointment, recorded by the doctor during
        // the visit (not patient self-reported — see savePrescription()).
        // patient_id is denormalized here for fast "latest vitals for this
        // patient" lookups, matching the same pattern already used by
        // prescriptions / referrals / patient_documents.
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse_rate')->nullable();
            $table->decimal('temperature_celsius', 4, 1)->nullable();
            $table->unsignedTinyInteger('spo2')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
