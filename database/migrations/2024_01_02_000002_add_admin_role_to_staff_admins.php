<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff_admins', function (Blueprint $table) {
            $table->enum('role', ['receptionist', 'accountant', 'manager', 'admin'])
                ->default('receptionist')
                ->change();
        });

        // username column যোগ করো যদি না থাকে
        if (!Schema::hasColumn('staff_admins', 'username')) {
            Schema::table('staff_admins', function (Blueprint $table) {
                $table->string('username', 50)->nullable()->unique()->after('email');
            });
        }
    }

    public function down(): void
    {
        Schema::table('staff_admins', function (Blueprint $table) {
            $table->enum('role', ['receptionist', 'accountant', 'manager'])
                ->default('receptionist')
                ->change();
        });
    }
};
