<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestPatientSeeder extends Seeder
{
    public function run(): void
    {
        // Remove existing test patient if exists
        $existing = DB::table('patients')->where('email', 'test@patient.com')->first();
        if ($existing) {
            $apptIds = DB::table('appointments')->where('patient_id', $existing->id)->pluck('id');
            DB::table('doctor_ratings')->where('patient_id', $existing->id)->delete();
            DB::table('prescriptions')->whereIn('appointment_id', $apptIds)->delete();
            DB::table('vitals')->whereIn('appointment_id', $apptIds)->delete();
            DB::table('appointments')->where('patient_id', $existing->id)->delete();
            DB::table('patients')->where('id', $existing->id)->delete();
        }

        $pwd = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

        // ── 1. Create Test Patient ────────────────────────────────
        $pid = DB::table('patients')->insertGetId([
            'first_name'    => 'Rakib',
            'last_name'     => 'Hossain',
            'email'         => 'test@patient.com',
            'contact'       => '01712-345678',
            'password'      => $pwd,
            'gender'        => 'Male',
            'date_of_birth' => '1992-06-15',
            'blood_group'   => 'B+',
            'address'       => 'House 12, Road 5, Mirpur-2, Dhaka-1216',
            // Deliberately does NOT overlap with anything in his prescription
            // history below — this allergy can only be caught by Safety
            // Check actually reading his profile, not by anything visibly
            // suggestive already sitting in his prescribed-medicines history.
            'known_allergies' => 'Tramadol and other opioid-class painkillers',
            'chronic_conditions' => null, // deliberately null — comes entirely from visit history below, to prove that path still works
            'current_medications' => 'Amlodipine 5mg daily (for blood pressure)',
            'family_medical_history' => 'Father had a heart attack at 55; mother has Type 2 Diabetes',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // ── 2. Get doctor IDs ─────────────────────────────────────
        $doc = fn($email) => DB::table('doctors')->where('email', $email)->value('id');

        $doctors = [
            'cardiology'    => $doc('saleh.ahmed@medicare-bd.com'),
            'neurology'     => $doc('karim.uddin@medicare-bd.com'),
            'general'       => $doc('mofazzal.hossain@medicare-bd.com'),
            'gastro'        => $doc('farhana.begum@medicare-bd.com'),
            'endocrinology' => $doc('nadia.rahman@medicare-bd.com'),
            'psychiatry'    => $doc('arif.chowdhury@medicare-bd.com'),
            'orthopedics'   => $doc('rafiqul.islam@medicare-bd.com'),
        ];

        // ── 3. Past Completed Appointments + Prescriptions ────────
        $past = [
            [
                'doc'         => 'cardiology',
                'date'        => now()->subMonths(5)->subDays(3)->toDateString(),
                'slot'        => '10:00',
                'diagnosis'   => 'Mild Hypertension (Stage 1)',
                'medicines'   => "Amlodipine 5mg - 1 tablet daily at night\nAspirin 75mg - 1 tablet daily after breakfast\nAtenolol 50mg - half tablet twice daily",
                'instruction' => 'Avoid salty food and smoking. Check blood pressure weekly. Exercise 30 mins daily.',
                'followup'    => now()->subMonths(4)->toDateString(),
                'rating'      => 5,
                'vitals'      => ['bp_systolic' => 148, 'bp_diastolic' => 94, 'pulse_rate' => 82, 'temperature_celsius' => 36.7, 'spo2' => 98],
            ],
            [
                'doc'         => 'general',
                'date'        => now()->subMonths(4)->subDays(7)->toDateString(),
                'slot'        => '11:00',
                'diagnosis'   => 'Viral Upper Respiratory Infection with Fever',
                'medicines'   => "Paracetamol 500mg - 1 tablet every 6 hours for fever\nAmoxicillin 500mg - 1 capsule 3 times daily for 5 days\nFexofenadine 120mg - 1 tablet daily at night\nOral Saline - 2 sachets daily",
                'instruction' => 'Rest adequately. Drink plenty of warm fluids. Avoid cold water. Steam inhalation twice daily.',
                'followup'    => now()->subMonths(4)->addDays(5)->toDateString(),
                'rating'      => 4,
                'vitals'      => ['bp_systolic' => 145, 'bp_diastolic' => 90, 'pulse_rate' => 88, 'temperature_celsius' => 38.9, 'spo2' => 96],
            ],
            [
                'doc'         => 'endocrinology',
                'date'        => now()->subMonths(3)->subDays(2)->toDateString(),
                'slot'        => '09:30',
                'diagnosis'   => 'Type 2 Diabetes Mellitus - Newly Diagnosed',
                'medicines'   => "Metformin 500mg - 1 tablet twice daily with meals\nGlibenclamide 5mg - half tablet daily before breakfast\nMultivitamin tablet - 1 daily after lunch",
                'instruction' => 'Check fasting blood sugar every morning. Avoid sugar, white rice, and processed food. Walk 30 minutes daily.',
                'followup'    => now()->subMonths(2)->toDateString(),
                'rating'      => 5,
            ],
            [
                'doc'         => 'gastro',
                'date'        => now()->subMonths(2)->subDays(10)->toDateString(),
                'slot'        => '10:30',
                'diagnosis'   => 'Gastroesophageal Reflux Disease (GERD) with Gastritis',
                'medicines'   => "Omeprazole 20mg - 1 capsule 30 mins before breakfast\nDomperidone 10mg - 1 tablet 3 times daily before meals\nAntacid suspension - 2 teaspoons after meals and at bedtime",
                'instruction' => 'Eat small meals every 3-4 hours. Avoid spicy and oily foods. Do not lie down immediately after eating.',
                'followup'    => now()->subMonths(1)->toDateString(),
                'rating'      => 4,
            ],
            [
                'doc'         => 'neurology',
                'date'        => now()->subMonths(1)->subDays(5)->toDateString(),
                'slot'        => '09:00',
                'diagnosis'   => 'Chronic Tension Headache with Cervicogenic Component',
                'medicines'   => "Naproxen 500mg - 1 tablet twice daily after meals for 7 days\nAmitriptyline 10mg - 1 tablet at bedtime for 1 month\nMethocarbamol 750mg - 1 tablet 3 times daily for 5 days",
                'instruction' => 'Avoid screen time more than 2 hours continuously. Apply warm compress to neck. Neck stretching exercises twice daily.',
                'followup'    => now()->addDays(25)->toDateString(),
                'rating'      => 5,
            ],
            [
                'doc'         => 'psychiatry',
                'date'        => now()->subDays(18)->toDateString(),
                'slot'        => '11:00',
                'diagnosis'   => 'Generalised Anxiety Disorder with Insomnia',
                'medicines'   => "Escitalopram 10mg - 1 tablet daily in the morning for 3 months\nClonazepam 0.5mg - 1 tablet at bedtime if needed (max 2 weeks)\nMelatonin 3mg - 1 tablet 30 mins before sleep",
                'instruction' => 'Practice deep breathing 10 minutes daily. Maintain consistent sleep schedule. Avoid caffeine after 3pm.',
                'followup'    => now()->addDays(10)->toDateString(),
                'rating'      => 5,
                'vitals'      => ['bp_systolic' => 152, 'bp_diastolic' => 96, 'pulse_rate' => 76, 'temperature_celsius' => 36.6, 'spo2' => 97],
            ],
            [
                // Deliberately worded differently from the "Mild Hypertension
                // (Stage 1)" diagnosis above — same underlying condition, on
                // purpose, to test that the AI recognizes them as one
                // recurring condition instead of two unrelated ones. BP is
                // worse again despite escalated treatment, continuing the
                // trend from the two vitals entries above.
                'doc'         => 'cardiology',
                'date'        => now()->subDays(5)->toDateString(),
                'slot'        => '10:00',
                'diagnosis'   => 'Hypertension Follow-up - Blood Pressure Review',
                'medicines'   => "Amlodipine 10mg - 1 tablet daily at night (dose increased)\nLosartan 50mg - 1 tablet daily in the morning (newly added)\nAspirin 75mg - 1 tablet daily after breakfast",
                'instruction' => 'Blood pressure still above target despite treatment. Strict low-salt diet. Recheck in 2 weeks.',
                'followup'    => now()->addDays(14)->toDateString(),
                'rating'      => 5,
                'vitals'      => ['bp_systolic' => 156, 'bp_diastolic' => 98, 'pulse_rate' => 80, 'temperature_celsius' => 36.8, 'spo2' => 97],
            ],
            [
                // The HARD test case. Not an allergy — a genuine multi-factor
                // reasoning problem: this establishes real kidney impairment
                // as a natural consequence of his ongoing, poorly-controlled
                // hypertension above, PLUS he's now on Losartan (an ARB).
                // Prescribing an NSAID later creates two separate non-allergy
                // problems at once — an NSAID+ARB interaction, and NSAID
                // nephrotoxicity on top of already-reduced kidney function —
                // neither catchable by simple allergy-list matching.
                'doc'         => 'general',
                'date'        => now()->subDays(2)->toDateString(),
                'slot'        => '10:00',
                'diagnosis'   => 'Hypertensive Nephropathy - Early Stage, mild proteinuria on routine screening',
                'medicines'   => "Continue current blood pressure medications as prescribed\nIncreased water intake, strict low-sodium diet\nAvoid NSAIDs and other nephrotoxic medications completely",
                'instruction' => 'Kidney function needs close monitoring given his hypertension history. He must avoid over-the-counter pain relievers such as ibuprofen or naproxen without checking first — they can worsen kidney function and interact with his Losartan.',
                'followup'    => now()->addDays(30)->toDateString(),
                'rating'      => 5,
                'vitals'      => ['bp_systolic' => 150, 'bp_diastolic' => 94, 'pulse_rate' => 78, 'temperature_celsius' => 36.7, 'spo2' => 97],
            ],
        ];

        foreach ($past as $a) {
            $did = $doctors[$a['doc']];
            if (!$did) continue;

            $apptId = DB::table('appointments')->insertGetId([
                'patient_id'       => $pid,
                'doctor_id'        => $did,
                'appointment_date' => $a['date'],
                'slot_start'       => $a['slot'],
                'slot_end'         => date('H:i', strtotime($a['slot']) + 900),
                'status'           => 'completed',
                'visit_notes'      => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('prescriptions')->insertOrIgnore([
                'appointment_id' => $apptId,
                'patient_id'     => $pid,
                'doctor_id'      => $did,
                'diagnosis'      => $a['diagnosis'],
                'medicines'      => $a['medicines'],
                'instructions'   => $a['instruction'],
                'follow_up_date' => $a['followup'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('doctor_ratings')->insertOrIgnore([
                'doctor_id'      => $did,
                'patient_id'     => $pid,
                'appointment_id' => $apptId,
                'rating'         => $a['rating'],
                'review'         => 'Excellent consultation. Very satisfied.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            if (isset($a['vitals'])) {
                DB::table('vitals')->insertOrIgnore(array_merge($a['vitals'], [
                    'appointment_id' => $apptId,
                    'patient_id'     => $pid,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]));
            }
        }

        // ── 4. Upcoming Appointments — two, so both the simple and the
        // hard Safety Check test can be run without reseeding between them.
        $upDocId = $doctors['general'];
        if ($upDocId) {
            // TEST 1 (simple): prescribe Tramadol here → direct allergy match.
            DB::table('appointments')->insertGetId([
                'patient_id'       => $pid,
                'doctor_id'        => $upDocId,
                'appointment_date' => now()->addDays(3)->toDateString(),
                'slot_start'       => '10:00',
                'slot_end'         => '10:15',
                'status'           => 'active',
                'visit_notes'      => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            // TEST 2 (hard): prescribe an NSAID (Ibuprofen/Naproxen) here for
            // any complaint → should catch BOTH the NSAID+Losartan interaction
            // AND the nephrotoxicity risk from his kidney impairment above —
            // neither is a direct allergy match, so it requires the AI to
            // actually reason about drug interactions and connect the dots
            // to a diagnosis, not just check a list.
            DB::table('appointments')->insertGetId([
                'patient_id'       => $pid,
                'doctor_id'        => $upDocId,
                'appointment_date' => now()->addDays(5)->toDateString(),
                'slot_start'       => '11:00',
                'slot_end'         => '11:15',
                'status'           => 'active',
                'visit_notes'      => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // ── 5. Bulk historical appointments for Analytics ─────────
        $allDocs  = array_values(array_filter($doctors));
        $slots    = ['08:30','09:00','09:30','10:00','10:30','11:00','11:30','14:00','14:30','15:00','15:30','16:00'];
        $statuses = ['completed','completed','completed','cancelled_by_patient'];
        $diagnoses = ['Viral fever','Hypertension follow-up','Diabetes monitoring','Gastritis','Tension headache','Anxiety disorder','Back pain','Sinusitis','UTI','Skin allergy'];

        srand(42);
        $inserted = 0;
        for ($i = 0; $i < 80 && $inserted < 65; $i++) {
            $daysAgo = rand(1, 88);
            $did     = $allDocs[array_rand($allDocs)];
            $slot    = $slots[array_rand($slots)];
            $status  = $statuses[array_rand($statuses)];
            $date    = now()->subDays($daysAgo)->toDateString();

            $exists = DB::table('appointments')
                ->where('doctor_id', $did)
                ->where('appointment_date', $date)
                ->where('slot_start', $slot)
                ->exists();
            if ($exists) continue;

            $apptId = DB::table('appointments')->insertGetId([
                'patient_id'       => $pid,
                'doctor_id'        => $did,
                'appointment_date' => $date,
                'slot_start'       => $slot,
                'slot_end'         => date('H:i', strtotime($slot) + 900),
                'status'           => $status,
                'visit_notes'      => null,
                'created_at'       => now()->subDays($daysAgo),
                'updated_at'       => now()->subDays($daysAgo),
            ]);

            if ($status === 'completed' && rand(0, 1)) {
                DB::table('prescriptions')->insertOrIgnore([
                    'appointment_id' => $apptId,
                    'patient_id'     => $pid,
                    'doctor_id'      => $did,
                    'diagnosis'      => $diagnoses[array_rand($diagnoses)],
                    'medicines'      => "Medicine as prescribed\nDose as directed by doctor",
                    'instructions'   => 'Complete the full course. Return if symptoms persist.',
                    'follow_up_date' => now()->subDays($daysAgo - 14)->toDateString(),
                    'created_at'     => now()->subDays($daysAgo),
                    'updated_at'     => now()->subDays($daysAgo),
                ]);
            }
            $inserted++;
        }

        $this->command->info('');
        $this->command->info('✅ TEST PATIENT CREATED!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📧 Email    : test@patient.com');
        $this->command->info('🔑 Password : password');
        $this->command->info('👤 Name     : Rakib Hossain | Male | Age 32 | B+');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ 8 completed visits with prescriptions');
        $this->command->info('✅ 2 upcoming appointments, both with');
        $this->command->info('   Dr. Mofazzal Hossain (General Medicine)');
        $this->command->info('✅ 65+ historical data for Analytics charts');
        $this->command->info('✅ Hypertension + Diabetes → Health Risk = Medium-High');
        $this->command->info('✅ 5 vitals entries, BP staying elevated across');
        $this->command->info('   every single visit (145-156 systolic) despite');
        $this->command->info('   escalating treatment — never once normal');
        $this->command->info('✅ "Mild Hypertension (Stage 1)" (5mo ago) and');
        $this->command->info('   "Hypertension Follow-up - Blood Pressure Review"');
        $this->command->info('   (5 days ago) are the SAME condition worded');
        $this->command->info('   differently — tests semantic diagnosis matching');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('SAFETY CHECK — TWO tests, one per upcoming visit:');
        $this->command->info('');
        $this->command->info('  TEST 1 (simple): prescribe Tramadol.');
        $this->command->info('  → Direct allergy match. Not mentioned anywhere');
        $this->command->info('    in his visit history, only on his profile.');
        $this->command->info('');
        $this->command->info('  TEST 2 (hard): prescribe Ibuprofen or Naproxen.');
        $this->command->info('  → NOT an allergy. Should catch TWO separate');
        $this->command->info('    problems: an NSAID+Losartan interaction, and');
        $this->command->info('    NSAID nephrotoxicity given his kidney');
        $this->command->info('    impairment noted 2 days ago — genuine');
        $this->command->info('    reasoning, not a list lookup.');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
