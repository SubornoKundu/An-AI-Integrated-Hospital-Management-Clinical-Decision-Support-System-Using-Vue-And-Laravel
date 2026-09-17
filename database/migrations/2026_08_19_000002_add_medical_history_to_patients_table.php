<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->text('known_allergies')->nullable()->after('address');
            $table->text('chronic_conditions')->nullable()->after('known_allergies');
            $table->text('current_medications')->nullable()->after('chronic_conditions');
            $table->text('family_medical_history')->nullable()->after('current_medications');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'known_allergies', 'chronic_conditions',
                'current_medications', 'family_medical_history',
            ]);
        });
    }
};
