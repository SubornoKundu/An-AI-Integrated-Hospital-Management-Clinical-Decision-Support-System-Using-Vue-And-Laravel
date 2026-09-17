<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Appointments.status is a hard MySQL ENUM — adding a value needs
        // an explicit MODIFY, a plain UPDATE to an unlisted value would be
        // rejected. Needed so admin-triggered cancellations (e.g. a holiday
        // declared on a date with existing bookings) are distinguishable
        // from a patient or doctor choosing to cancel.
        //
        // "ALTER TABLE ... MODIFY COLUMN ... ENUM(...)" is MySQL-only
        // syntax — it doesn't parse on SQLite at all (reproduced directly:
        // `near "MODIFY": syntax error`). The test suite runs every
        // migration against SQLite (see phpunit.xml), so this was failing
        // every single feature test at the migration step, before any test
        // code even ran. SQLite has no native ENUM type in the first
        // place — Laravel represents `$table->enum()` there as a CHECK
        // constraint — so the fix isn't "make MySQL syntax work on
        // SQLite", it's "use the schema builder so each driver gets its
        // own correct representation". Verified directly against Laravel's
        // real SQLite grammar (not just reasoned about): the constraint is
        // still enforced, the new value is accepted, and both existing
        // rows and rows in tables with a foreign key to appointments
        // survive the SQLite recreate-table step this triggers.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('appointments', function (Blueprint $table) {
                $table->enum('status', ['active', 'completed', 'cancelled_by_patient', 'cancelled_by_doctor', 'cancelled_by_admin'])
                    ->default('active')
                    ->change();
            });
        } else {
            DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('active', 'completed', 'cancelled_by_patient', 'cancelled_by_doctor', 'cancelled_by_admin') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('appointments', function (Blueprint $table) {
                $table->enum('status', ['active', 'completed', 'cancelled_by_patient', 'cancelled_by_doctor'])
                    ->default('active')
                    ->change();
            });
        } else {
            DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('active', 'completed', 'cancelled_by_patient', 'cancelled_by_doctor') NOT NULL DEFAULT 'active'");
        }
    }
};
