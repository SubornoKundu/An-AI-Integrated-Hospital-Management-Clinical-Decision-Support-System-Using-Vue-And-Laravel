<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('icon', 10)->default('🩺');
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('doctor_categories')->insert([
            ['name' => 'Cardiology',       'icon' => '🫀', 'description' => 'Heart & cardiovascular diseases'],
            ['name' => 'Neurology',        'icon' => '🧠', 'description' => 'Brain, spine & nervous system'],
            ['name' => 'Orthopedics',      'icon' => '🦴', 'description' => 'Bones, joints & muscles'],
            ['name' => 'Pediatrics',       'icon' => '👶', 'description' => 'Children health (0–18 years)'],
            ['name' => 'Gynecology',       'icon' => '👩', 'description' => 'Women reproductive health'],
            ['name' => 'General Medicine', 'icon' => '💊', 'description' => 'Common illnesses & check-ups'],
            ['name' => 'Dermatology',      'icon' => '🔬', 'description' => 'Skin, hair & nail conditions'],
            ['name' => 'ENT',              'icon' => '👂', 'description' => 'Ear, nose & throat'],
            ['name' => 'Ophthalmology',    'icon' => '👁',  'description' => 'Eyes & vision'],
            ['name' => 'Urology',          'icon' => '🫘', 'description' => 'Kidney & urinary tract'],
            ['name' => 'Gastroenterology', 'icon' => '🫁', 'description' => 'Digestive system'],
            ['name' => 'Psychiatry',       'icon' => '🧘', 'description' => 'Mental health'],
            ['name' => 'Oncology',         'icon' => '🎗',  'description' => 'Cancer treatment'],
            ['name' => 'Endocrinology',    'icon' => '⚗',  'description' => 'Hormones & diabetes'],
            ['name' => 'Dentistry',        'icon' => '🦷', 'description' => 'Teeth & oral health'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_categories');
    }
};
