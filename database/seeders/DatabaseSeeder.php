<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Categories ───────────────────────────────────────────
        $cats = [
            ['name'=>'Cardiology',       'icon'=>'🫀','description'=>'Heart & cardiovascular diseases'],
            ['name'=>'Neurology',        'icon'=>'🧠','description'=>'Brain, spine & nervous system'],
            ['name'=>'Orthopedics',      'icon'=>'🦴','description'=>'Bones, joints & muscles'],
            ['name'=>'Pediatrics',       'icon'=>'👶','description'=>'Children health (0–18 years)'],
            ['name'=>'Gynecology',       'icon'=>'🌸','description'=>'Women reproductive health'],
            ['name'=>'General Medicine', 'icon'=>'💊','description'=>'Common illnesses & check-ups'],
            ['name'=>'Dermatology',      'icon'=>'🔬','description'=>'Skin, hair & nail conditions'],
            ['name'=>'ENT',              'icon'=>'👂','description'=>'Ear, nose & throat'],
            ['name'=>'Ophthalmology',    'icon'=>'👁','description'=>'Eyes & vision care'],
            ['name'=>'Urology',          'icon'=>'🫘','description'=>'Kidney & urinary tract'],
            ['name'=>'Gastroenterology', 'icon'=>'🫁','description'=>'Stomach & digestive system'],
            ['name'=>'Psychiatry',       'icon'=>'🧘','description'=>'Mental health & wellness'],
            ['name'=>'Oncology',         'icon'=>'🎗','description'=>'Cancer diagnosis & treatment'],
            ['name'=>'Endocrinology',    'icon'=>'⚗', 'description'=>'Hormones, thyroid & diabetes'],
            ['name'=>'Dentistry',        'icon'=>'🦷','description'=>'Teeth, gums & oral health'],
        ];
        foreach ($cats as $cat) {
            DB::table('doctor_categories')->updateOrInsert(
                ['name' => $cat['name']],
                array_merge($cat, ['is_active' => 1])
            );
        }

        $cid = fn($name) => DB::table('doctor_categories')->where('name', $name)->value('id');

        // Password rule: email prefix (before dot) + "123"
        // e.g. saleh.ahmed@... → saleh123
        $mkpwd = fn($email) => \Illuminate\Support\Facades\Hash::make(explode('.', explode('@', $email)[0])[0] . '123');

        $doctors = [
            // Cardiology
            ['email'=>'saleh.ahmed@medicare-bd.com',    'name'=>'Dr. Saleh Ahmed',      'cat'=>'Cardiology',      'fee'=>800, 'bio'=>'Interventional Cardiologist with 18 years of experience. MBBS (DMCH), MD Cardiology (BSMMU). Specialist in angioplasty and heart failure management.', 'phone'=>'01711-000001',
             'schedules'=>[0=>['09:00','14:00'],1=>['09:00','17:00'],3=>['09:00','17:00'],4=>['09:00','14:00']]],
            ['email'=>'nasrin.sultana@medicare-bd.com', 'name'=>'Dr. Nasrin Sultana',   'cat'=>'Cardiology',      'fee'=>900, 'bio'=>'Senior Consultant Cardiologist. FCPS (Cardiology). Specialist in echocardiography and preventive cardiology.', 'phone'=>'01711-000002',
             'schedules'=>[1=>['10:00','18:00'],2=>['10:00','18:00'],5=>['09:00','13:00']]],
            // Neurology
            ['email'=>'karim.uddin@medicare-bd.com',   'name'=>'Dr. Karim Uddin',       'cat'=>'Neurology',       'fee'=>700, 'bio'=>'Neurologist and Epilepsy Specialist. DM Neurology (BSMMU). Expertise in stroke, epilepsy and movement disorders.', 'phone'=>'01711-000003',
             'schedules'=>[0=>['09:00','14:00'],1=>['09:00','16:00'],3=>['09:00','16:00']]],
            // Urology
            ['email'=>'fatema.khanam@medicare-bd.com', 'name'=>'Dr. Fatema Khanam',     'cat'=>'Urology',         'fee'=>600, 'bio'=>'Urologist and Kidney Specialist. MS Urology. Specialist in kidney stones, UTI, and laparoscopic urological procedures.', 'phone'=>'01711-000004',
             'schedules'=>[2=>['10:00','17:00'],4=>['10:00','17:00'],6=>['09:00','13:00']]],
            // Orthopedics
            ['email'=>'rafiqul.islam@medicare-bd.com', 'name'=>'Dr. Rafiqul Islam',     'cat'=>'Orthopedics',     'fee'=>550, 'bio'=>'Orthopedic Surgeon specializing in joint replacement and sports injuries. MS Orthopedics. 10 years experience.', 'phone'=>'01711-000005',
             'schedules'=>[0=>['08:00','14:00'],2=>['08:00','14:00'],4=>['08:00','12:00']]],
            // Pediatrics
            ['email'=>'shahana.parvin@medicare-bd.com','name'=>'Dr. Shahana Parvin',    'cat'=>'Pediatrics',      'fee'=>450, 'bio'=>'Consultant Pediatrician. FCPS Paediatrics. Specialist in childhood nutrition, growth disorders, and vaccines.', 'phone'=>'01711-000006',
             'schedules'=>[0=>['09:00','17:00'],1=>['09:00','17:00'],3=>['09:00','17:00']]],
            // General Medicine
            ['email'=>'mofazzal.hossain@medicare-bd.com','name'=>'Dr. Mofazzal Hossain','cat'=>'General Medicine', 'fee'=>400, 'bio'=>'General Physician and Internal Medicine Specialist. MBBS, FCPS. Managing chronic diseases, fever, diabetes and hypertension.', 'phone'=>'01711-000007',
             'schedules'=>[0=>['09:00','14:00'],1=>['09:00','18:00'],2=>['09:00','18:00'],3=>['09:00','18:00'],4=>['09:00','18:00']]],
            // Gynecology
            ['email'=>'rumana.akter@medicare-bd.com',  'name'=>'Dr. Rumana Akter',      'cat'=>'Gynecology',      'fee'=>650, 'bio'=>'Gynecologist and Obstetrician. FCPS (Gynecology). Specialist in high-risk pregnancy, laparoscopic surgery, and infertility.', 'phone'=>'01711-000008',
             'schedules'=>[0=>['10:00','16:00'],2=>['10:00','16:00'],4=>['10:00','16:00']]],
            // Dermatology
            ['email'=>'tanvir.hasan@medicare-bd.com',  'name'=>'Dr. Tanvir Hasan',      'cat'=>'Dermatology',     'fee'=>500, 'bio'=>'Dermatologist and Skin Specialist. MD Dermatology. Expertise in acne, eczema, psoriasis and cosmetic dermatology.', 'phone'=>'01711-000009',
             'schedules'=>[1=>['10:00','17:00'],3=>['10:00','17:00'],5=>['09:00','13:00']]],
            // ENT
            ['email'=>'sumaiya.islam@medicare-bd.com', 'name'=>'Dr. Sumaiya Islam',     'cat'=>'ENT',             'fee'=>480, 'bio'=>'ENT Specialist. MS ENT. Expert in ear, nose and throat disorders including hearing loss, sinusitis and tonsillitis.', 'phone'=>'01711-000010',
             'schedules'=>[1=>['09:00','16:00'],3=>['09:00','16:00'],5=>['09:00','13:00']]],
            // Ophthalmology
            ['email'=>'rezaul.karim@medicare-bd.com',  'name'=>'Dr. Rezaul Karim',      'cat'=>'Ophthalmology',   'fee'=>520, 'bio'=>'Ophthalmologist and Eye Surgeon. MS Ophthalmology. Specialist in cataract surgery, glaucoma and refractive disorders.', 'phone'=>'01711-000011',
             'schedules'=>[0=>['09:00','14:00'],2=>['09:00','14:00'],4=>['09:00','14:00']]],
            // Gastroenterology
            ['email'=>'farhana.begum@medicare-bd.com', 'name'=>'Dr. Farhana Begum',     'cat'=>'Gastroenterology','fee'=>600, 'bio'=>'Gastroenterologist. DM Gastroenterology. Specialist in liver disease, IBS, peptic ulcer and endoscopy procedures.', 'phone'=>'01711-000012',
             'schedules'=>[1=>['10:00','17:00'],3=>['10:00','17:00'],4=>['10:00','15:00']]],
            // Psychiatry
            ['email'=>'arif.chowdhury@medicare-bd.com','name'=>'Dr. Arif Chowdhury',    'cat'=>'Psychiatry',      'fee'=>600, 'bio'=>'Consultant Psychiatrist. MD Psychiatry. Expertise in depression, anxiety, schizophrenia and addiction management.', 'phone'=>'01711-000013',
             'schedules'=>[1=>['10:00','16:00'],3=>['10:00','16:00'],5=>['10:00','14:00']]],
            // Endocrinology
            ['email'=>'nadia.rahman@medicare-bd.com',  'name'=>'Dr. Nadia Rahman',      'cat'=>'Endocrinology',   'fee'=>650, 'bio'=>'Endocrinologist. MD Endocrinology. Specialist in diabetes management, thyroid disorders and hormonal conditions.', 'phone'=>'01711-000014',
             'schedules'=>[0=>['09:00','14:00'],2=>['09:00','14:00'],4=>['09:00','14:00']]],
            // Dentistry
            ['email'=>'imran.hossain@medicare-bd.com', 'name'=>'Dr. Imran Hossain',     'cat'=>'Dentistry',       'fee'=>400, 'bio'=>'Dental Surgeon. BDS, MS Oral Surgery. Specialist in root canal, dental implants and cosmetic dentistry.', 'phone'=>'01711-000015',
             'schedules'=>[0=>['09:00','17:00'],1=>['09:00','17:00'],2=>['09:00','17:00'],3=>['09:00','17:00'],4=>['09:00','17:00']]],
            // Oncology
            ['email'=>'taslima.khatun@medicare-bd.com','name'=>'Dr. Taslima Khatun',    'cat'=>'Oncology',        'fee'=>900, 'bio'=>'Medical Oncologist. MD Oncology. Specialist in chemotherapy, cancer screening and palliative care.', 'phone'=>'01711-000016',
             'schedules'=>[1=>['09:00','15:00'],3=>['09:00','15:00'],5=>['09:00','13:00']]],
        ];

        $days = ['0'=>'Sun','1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat'];

        foreach ($doctors as $d) {
            if (DB::table('doctors')->where('email', $d['email'])->exists()) continue;

            $catId = $cid($d['cat']);
            if (!$catId) continue;

            $did = DB::table('doctors')->insertGetId([
                'name'            => $d['name'],
                'email'           => $d['email'],
                'password'        => $mkpwd($d['email']),
                'category_id'     => $catId,
                'consultancy_fee' => $d['fee'],
                'bio'             => $d['bio'],
                'phone'           => $d['phone'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($d['schedules'] as $day => [$start, $end]) {
                DB::table('doctor_schedules')->updateOrInsert(
                    ['doctor_id' => $did, 'day_of_week' => $day],
                    ['start_time' => $start, 'end_time' => $end, 'is_active' => 1]
                );
            }

            // Lunch break Mon–Thu
            foreach ([1,2,3,4] as $lunchDay) {
                if (isset($d['schedules'][$lunchDay])) {
                    DB::table('doctor_breaks')->insertOrIgnore([
                        'doctor_id'   => $did,
                        'day_of_week' => $lunchDay,
                        'break_start' => '13:00',
                        'break_end'   => '13:30',
                        'label'       => 'Lunch',
                    ]);
                }
            }
        }

        // Hospital settings
        foreach ([
            'hospital_name'    => 'MediCare Hospital',
            'hospital_address' => 'House 45, Road 12, Dhanmondi, Dhaka-1209, Bangladesh',
            'hospital_phone'   => '+880 2-9876543',
            'hospital_email'   => 'info@medicare-bd.com',
            'slot_duration'    => '15',
            'currency_symbol'  => '৳',
        ] as $k => $v) {
            DB::table('hospital_settings')->updateOrInsert(['skey' => $k], ['sval' => $v]);
        }

        $this->command->info('✅ Seeded: 16 doctors across all 15 specialties');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('Doctor login passwords (first name + 123):');
        $this->command->info('  saleh.ahmed@medicare-bd.com    → saleh123');
        $this->command->info('  nasrin.sultana@medicare-bd.com → nasrin123');
        $this->command->info('  karim.uddin@medicare-bd.com    → karim123');
        $this->command->info('  (same rule for all doctors)');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Test patients for AI feature testing
        $this->call(TestPatientSeeder::class);

        // Wards and beds
        $this->call(BedManagementSeeder::class);
    }
}
