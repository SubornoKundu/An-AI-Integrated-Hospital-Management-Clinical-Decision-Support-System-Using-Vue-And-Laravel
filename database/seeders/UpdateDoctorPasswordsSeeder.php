<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateDoctorPasswordsSeeder extends Seeder
{
    /**
     * Update existing doctors' passwords to name-based format.
     * Rule: email prefix before first dot + "123"
     * e.g. saleh.ahmed@medicare-bd.com → saleh123
     */
    public function run(): void
    {
        $doctors = DB::table('doctors')->get();

        $updated = 0;
        foreach ($doctors as $doctor) {
            // Extract first part of email before the dot
            $emailPrefix = explode('@', $doctor->email)[0];       // saleh.ahmed
            $firstName   = explode('.', $emailPrefix)[0];          // saleh
            $newPassword = $firstName . '123';                     // saleh123

            DB::table('doctors')
                ->where('id', $doctor->id)
                ->update(['password' => Hash::make($newPassword)]);

            $updated++;
            $this->command->info("✅ {$doctor->name} → password: {$newPassword}");
        }

        $this->command->info('');
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("Updated {$updated} doctor passwords.");
        $this->command->info("Rule: [first name in lowercase] + 123");
        $this->command->info("Example: saleh.ahmed@medicare-bd.com → saleh123");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    }
}
