<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'gender')) {
                $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('phone');
            }
        });

        Schema::table('staff_admins', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_admins', 'gender')) {
                $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'gender')) {
                $table->dropColumn('gender');
            }
        });

        Schema::table('staff_admins', function (Blueprint $table) {
            if (Schema::hasColumn('staff_admins', 'gender')) {
                $table->dropColumn('gender');
            }
        });
    }
};
