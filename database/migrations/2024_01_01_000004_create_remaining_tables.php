<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doctor Schedules
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->comment('0=Sun 1=Mon ... 6=Sat');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->unique(['doctor_id', 'day_of_week']);
        });

        // Doctor Breaks
        Schema::create('doctor_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->time('break_start');
            $table->time('break_end');
            $table->string('label', 50)->default('Break');
        });

        // Doctor Blocked Dates
        Schema::create('doctor_blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('blocked_date');
            $table->string('reason', 200)->default('Leave');
            $table->unique(['doctor_id', 'blocked_date']);
        });

        // Hospital Holidays
        Schema::create('hospital_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        // Prescription Templates
        Schema::create('prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->string('template_name', 100);
            $table->text('diagnosis');
            $table->text('medicines');
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        // Patient Favourites
        Schema::create('patient_favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['patient_id', 'doctor_id']);
        });

        // Login Attempts
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 100);
            $table->string('role', 20);
            $table->timestamp('attempt_time')->useCurrent();
            $table->string('ip', 45)->nullable();
            $table->index(['identifier', 'role']);
        });

        // Appointments
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('slot_start');
            $table->time('slot_end');
            $table->enum('status', ['active', 'completed', 'cancelled_by_patient', 'cancelled_by_doctor'])->default('active');
            $table->text('visit_notes')->nullable();
            $table->timestamps();
        });

        // Prescriptions
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->text('diagnosis');
            $table->text('medicines');
            $table->text('instructions')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
        });

        // Referrals
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('to_category_id')->nullable()->constrained('doctor_categories')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Patient Documents
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50)->nullable();
            $table->enum('doc_type', ['lab_report', 'xray', 'prescription', 'other'])->default('other');
            $table->string('notes', 300)->nullable();
            $table->timestamps();
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('user_role', ['patient', 'doctor', 'admin']);
            $table->string('title', 200);
            $table->text('message');
            $table->enum('type', ['booking', 'cancellation', 'reminder', 'prescription', 'chat', 'general'])->default('general');
            $table->boolean('is_read')->default(false);
            $table->string('link', 300)->nullable();
            $table->timestamps();
        });

        // Doctor Ratings
        Schema::create('doctor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();
        });

        // Chat Messages
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_role', ['patient', 'doctor']);
            $table->unsignedBigInteger('sender_id');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->useCurrent();
        });

        // Staff Admins
        Schema::create('staff_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->enum('role', ['receptionist', 'accountant', 'manager'])->default('receptionist');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // Hospital Settings
        Schema::create('hospital_settings', function (Blueprint $table) {
            $table->string('skey', 50)->primary();
            $table->text('sval');
            $table->timestamps();
        });

        // Seed hospital settings
        \DB::table('hospital_settings')->insert([
            ['skey' => 'hospital_name',    'sval' => 'MediCare Hospital'],
            ['skey' => 'hospital_address', 'sval' => '123 Medical Lane, Dhanmondi, Dhaka-1205'],
            ['skey' => 'hospital_phone',   'sval' => '+880 1700-000000'],
            ['skey' => 'hospital_email',   'sval' => 'info@medicare.com.bd'],
            ['skey' => 'slot_duration',    'sval' => '15'],
            ['skey' => 'currency_symbol',  'sval' => '৳'],
            ['skey' => 'hospital_logo',    'sval' => ''],
        ]);

        // Contacts / Feedback
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100);
            $table->string('contact', 15);
            $table->text('message');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });

        // Password Resets
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email', 100);
            $table->enum('role', ['patient', 'doctor']);
            $table->string('token', 64)->unique();
            $table->dateTime('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('hospital_settings');
        Schema::dropIfExists('staff_admins');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('doctor_ratings');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('patient_documents');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('patient_favourites');
        Schema::dropIfExists('prescription_templates');
        Schema::dropIfExists('hospital_holidays');
        Schema::dropIfExists('doctor_blocked_dates');
        Schema::dropIfExists('doctor_breaks');
        Schema::dropIfExists('doctor_schedules');
    }
};
