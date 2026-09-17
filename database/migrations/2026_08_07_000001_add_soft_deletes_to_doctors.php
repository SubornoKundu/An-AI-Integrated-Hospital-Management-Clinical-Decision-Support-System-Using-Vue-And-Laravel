<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // With this column + SoftDeletes on the Doctor model, "delete doctor"
        // becomes an UPDATE (sets deleted_at), not a DELETE — so the
        // cascadeOnDelete() on appointments.doctor_id (and, transitively,
        // prescriptions/doctor_ratings/chat_messages via appointment_id)
        // never actually fires. Patient medical history stays intact even
        // after a doctor is removed from the active roster. See
        // app/Models/Doctor.php and the doctor() / fromDoctor() relationship
        // changes in Appointment, Prescription, DoctorRating, and Referral.
        Schema::table('doctors', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
