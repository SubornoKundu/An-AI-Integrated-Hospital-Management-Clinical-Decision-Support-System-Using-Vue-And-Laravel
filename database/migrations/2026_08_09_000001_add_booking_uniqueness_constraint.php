<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard rail, as requested: check for existing duplicate
        // active/completed bookings for the same doctor+date+slot BEFORE
        // attempting the constraint. Without this check, if any duplicate
        // already exists, the ALTER TABLE below would simply fail with a
        // generic MySQL "duplicate entry" error and no indication of which
        // rows are the problem or what to do about it.
        $duplicates = DB::table('appointments')
            ->select('doctor_id', 'appointment_date', 'slot_start', DB::raw('COUNT(*) as cnt'), DB::raw("GROUP_CONCAT(id) as ids"))
            ->whereIn('status', ['active', 'completed'])
            ->groupBy('doctor_id', 'appointment_date', 'slot_start')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $details = $duplicates->map(
                fn ($d) => "  doctor_id={$d->doctor_id} date={$d->appointment_date} slot={$d->slot_start} — appointment ids: {$d->ids}"
            )->implode("\n");

            throw new \RuntimeException(
                "Cannot add the booking-uniqueness constraint: found {$duplicates->count()} " .
                "existing double-booked slot(s) that would violate it:\n{$details}\n\n" .
                "Resolve these first — for each group above, cancel or reschedule all but one " .
                "appointment (the app-level lock added in an earlier phase prevents any *new* " .
                "duplicates, so this should only be pre-existing data, if anything) — then re-run " .
                "`php artisan migrate`."
            );
        }

        // CONCAT() is native MySQL. It does NOT work in SQLite despite
        // appearing to succeed when the column is first created — verified
        // directly: the ALTER TABLE succeeds either way, but SQLite only
        // resolves the function name when a row is actually inserted, where
        // it then fails with "unknown function: CONCAT()". Branching here
        // isn't precautionary — it's fixing a failure that was reproduced,
        // not guessed at. Needed for the test suite specifically: it runs
        // against SQLite (see phpunit.xml), and RefreshDatabase runs every
        // migration, so an incompatible one here would break every test.
        $driver = Schema::getConnection()->getDriverName();
        $concatExpr = $driver === 'sqlite'
            ? "doctor_id || '|' || appointment_date || '|' || slot_start"
            : "CONCAT(doctor_id,'|',appointment_date,'|',slot_start)";

        Schema::table('appointments', function (Blueprint $table) use ($concatExpr) {
            // NULL for cancelled bookings — a unique index treats multiple
            // NULLs as non-conflicting (true on both MySQL and SQLite,
            // verified), so a cancelled slot still frees up correctly for
            // someone else to book. Non-NULL (and therefore
            // uniqueness-enforced) only for active/completed rows.
            $table->string('booking_slot_key', 100)->nullable()->virtualAs(
                "CASE WHEN status IN ('active','completed') THEN {$concatExpr} ELSE NULL END"
            );
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unique('booking_slot_key', 'uniq_active_booking_slot');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique('uniq_active_booking_slot');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('booking_slot_key');
        });
    }
};
