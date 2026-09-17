<?php

namespace Database\Seeders;

use App\Models\HospitalBed;
use App\Models\HospitalWard;
use Illuminate\Database\Seeder;

/**
 * BedManagementSeeder
 *
 * Seeds sample wards and beds for the hospital.
 * Run with: php artisan db:seed --class=BedManagementSeeder
 */
class BedManagementSeeder extends Seeder
{
    public function run(): void
    {
        // ── Define wards ─────────────────────────────────────────
        $wards = [
            [
                'name'     => 'General Ward A',
                'type'     => 'general',
                'floor'    => '2',
                'building' => 'Main',
                'beds'     => [
                    ['number' => 'GA-101', 'type' => 'standard', 'charge' => 800],
                    ['number' => 'GA-102', 'type' => 'standard', 'charge' => 800],
                    ['number' => 'GA-103', 'type' => 'standard', 'charge' => 800],
                    ['number' => 'GA-104', 'type' => 'standard', 'charge' => 800],
                    ['number' => 'GA-105', 'type' => 'deluxe',   'charge' => 1500],
                    ['number' => 'GA-106', 'type' => 'deluxe',   'charge' => 1500],
                ],
            ],
            [
                'name'     => 'General Ward B',
                'type'     => 'general',
                'floor'    => '2',
                'building' => 'Main',
                'beds'     => [
                    ['number' => 'GB-101', 'type' => 'standard', 'charge' => 800],
                    ['number' => 'GB-102', 'type' => 'standard', 'charge' => 800],
                    ['number' => 'GB-103', 'type' => 'standard', 'charge' => 800],
                    ['number' => 'GB-104', 'type' => 'deluxe',   'charge' => 1500],
                ],
            ],
            [
                'name'     => 'ICU (Intensive Care)',
                'type'     => 'icu',
                'floor'    => '3',
                'building' => 'Main',
                'beds'     => [
                    ['number' => 'ICU-01', 'type' => 'icu', 'charge' => 8000],
                    ['number' => 'ICU-02', 'type' => 'icu', 'charge' => 8000],
                    ['number' => 'ICU-03', 'type' => 'icu', 'charge' => 8000],
                    ['number' => 'ICU-04', 'type' => 'icu', 'charge' => 8000],
                ],
            ],
            [
                'name'     => 'CCU (Cardiac Care)',
                'type'     => 'ccu',
                'floor'    => '3',
                'building' => 'Main',
                'beds'     => [
                    ['number' => 'CCU-01', 'type' => 'icu', 'charge' => 7000],
                    ['number' => 'CCU-02', 'type' => 'icu', 'charge' => 7000],
                    ['number' => 'CCU-03', 'type' => 'icu', 'charge' => 7000],
                ],
            ],
            [
                'name'     => 'Private Cabins',
                'type'     => 'cabin',
                'floor'    => '4',
                'building' => 'Annex',
                'beds'     => [
                    ['number' => 'CAB-01', 'type' => 'cabin', 'charge' => 4000],
                    ['number' => 'CAB-02', 'type' => 'cabin', 'charge' => 4000],
                    ['number' => 'CAB-03', 'type' => 'cabin', 'charge' => 5000, 'notes' => 'Corner suite with extra space'],
                    ['number' => 'CAB-04', 'type' => 'cabin', 'charge' => 5000],
                ],
            ],
            [
                'name'     => 'Maternity Ward',
                'type'     => 'maternity',
                'floor'    => '1',
                'building' => 'Annex',
                'beds'     => [
                    ['number' => 'MAT-01', 'type' => 'standard', 'charge' => 1200],
                    ['number' => 'MAT-02', 'type' => 'standard', 'charge' => 1200],
                    ['number' => 'MAT-03', 'type' => 'deluxe',   'charge' => 2500],
                ],
            ],
            [
                'name'     => 'Pediatric Ward',
                'type'     => 'pediatric',
                'floor'    => '1',
                'building' => 'Main',
                'beds'     => [
                    ['number' => 'PED-01', 'type' => 'standard', 'charge' => 900],
                    ['number' => 'PED-02', 'type' => 'standard', 'charge' => 900],
                    ['number' => 'PED-03', 'type' => 'standard', 'charge' => 900],
                ],
            ],
            [
                'name'     => 'Emergency Ward',
                'type'     => 'emergency',
                'floor'    => 'G',
                'building' => 'Main',
                'beds'     => [
                    ['number' => 'EM-01', 'type' => 'standard', 'charge' => 1000],
                    ['number' => 'EM-02', 'type' => 'standard', 'charge' => 1000],
                    ['number' => 'EM-03', 'type' => 'icu',      'charge' => 5000],
                    ['number' => 'EM-04', 'type' => 'standard', 'charge' => 1000],
                ],
            ],
        ];

        foreach ($wards as $wardData) {
            $beds = $wardData['beds'];
            unset($wardData['beds']);

            $ward = HospitalWard::create(array_merge($wardData, [
                'total_beds' => count($beds),
                'is_active'  => true,
            ]));

            foreach ($beds as $bed) {
                HospitalBed::create([
                    'ward_id'      => $ward->id,
                    'bed_number'   => $bed['number'],
                    'bed_type'     => $bed['type'],
                    'daily_charge' => $bed['charge'],
                    'status'       => 'available',
                    'notes'        => $bed['notes'] ?? null,
                ]);
            }
        }

        $totalBeds  = HospitalBed::count();
        $totalWards = HospitalWard::count();
        $this->command->info("✅ Seeded: {$totalWards} wards and {$totalBeds} beds");
    }
}
