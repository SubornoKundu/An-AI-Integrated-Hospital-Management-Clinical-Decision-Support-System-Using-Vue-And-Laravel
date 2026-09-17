<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hospital Wards
        Schema::create('hospital_wards', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // e.g. "General Ward A", "ICU", "Cardiology"
            $table->string('type')->default('general'); // general, icu, ccu, cabin, emergency
            $table->unsignedInteger('total_beds')->default(0);
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Individual Beds
        Schema::create('hospital_beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained('hospital_wards')->onDelete('cascade');
            $table->string('bed_number');              // e.g. "A-101", "ICU-03"
            $table->string('bed_type')->default('standard'); // standard, deluxe, icu, cabin
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved'])
                  ->default('available');
            $table->decimal('daily_charge', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['ward_id', 'bed_number']);
        });

        // Bed Admissions (Patient assigned to a bed)
        Schema::create('bed_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bed_id')->constrained('hospital_beds')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->date('admission_date');
            $table->date('discharge_date')->nullable();
            $table->string('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['admitted', 'discharged'])->default('admitted');
            $table->decimal('total_charge', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_admissions');
        Schema::dropIfExists('hospital_beds');
        Schema::dropIfExists('hospital_wards');
    }
};
