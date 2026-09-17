<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'photo')) {
                $table->string('photo')->nullable()->after('email');
            }
        });

        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'photo')) {
                $table->string('photo')->nullable()->after('email');
            }
        });

        Schema::table('staff_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_admins', 'photo')) {
                $table->string('photo')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'photo')) {
                $table->dropColumn('photo');
            }
        });

        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'photo')) {
                $table->dropColumn('photo');
            }
        });

        Schema::table('staff_admins', function (Blueprint $table) {
            if (Schema::hasColumn('staff_admins', 'photo')) {
                $table->dropColumn('photo');
            }
        });
    }
};
