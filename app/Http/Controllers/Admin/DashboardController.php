<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Doctor;
use App\Models\DoctorBlockedDate;
use App\Models\DoctorCategory;
use App\Models\DoctorSchedule;
use App\Models\HospitalHoliday;
use App\Models\HospitalBed;
use App\Models\HospitalSetting;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\StaffAdmin;
use App\Services\NotificationService;
use App\Services\ProfilePhotoService;
use App\Services\SvgSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected NotificationService $ns;

    protected ProfilePhotoService $photos;

    public function __construct(NotificationService $ns, ProfilePhotoService $photos)
    {
        $this->ns = $ns;
        $this->photos = $photos;
    }

    // ── Dashboard ────────────────────────────────────────────────
    public function dashboard(): Response
    {
        $stats = [
            'categories'   => DoctorCategory::where('is_active', 1)->count(),
            'doctors'      => Doctor::count(),
            'patients'     => Patient::count(),
            'appointments' => Appointment::count(),
            'revenue'      => Appointment::where('status','completed')
                ->join('doctors','appointments.doctor_id','=','doctors.id')
                ->sum(DB::raw('COALESCE(appointments.consultancy_fee, doctors.consultancy_fee)')),
            'today'        => Appointment::whereDate('appointment_date', today())->where('status','active')->count(),
            'pending'      => Contact::where('is_resolved', false)->count(),
            'available_beds' => HospitalBed::where('status','available')->count(),
        ];

        $topDoctors = DB::table('appointments')
            ->join('doctors','appointments.doctor_id','=','doctors.id')
            ->where('appointments.status','completed')
            ->selectRaw('doctors.id, doctors.name as doctor_name, SUM(COALESCE(appointments.consultancy_fee, doctors.consultancy_fee)) as revenue, COUNT(appointments.id) as cnt')
            ->groupBy('doctors.id','doctors.name')
            ->orderByDesc('revenue')
            ->limit(5)->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->doctor_name,
                'revenue' => (float) $d->revenue,
                'appointments' => (int) $d->cnt,
            ]);

        $recentAppointments = Appointment::with('patient','doctor.category')
            ->orderByDesc('created_at')->take(8)->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'status' => $a->status,
                'appointment_date' => $a->appointment_date?->format('d M Y'),
                'patient_name' => $a->patient?->full_name ?? '—',
                'doctor_name' => $a->doctor?->name ?? '—',
                'doctor_icon' => $a->doctor?->category?->icon ?? '🩺',
            ]);

        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $rev = Appointment::where('status','completed')
                ->whereYear('appointment_date', $m->year)
                ->whereMonth('appointment_date', $m->month)
                ->join('doctors','appointments.doctor_id','=','doctors.id')
                ->sum(DB::raw('COALESCE(appointments.consultancy_fee, doctors.consultancy_fee)'));
            $monthlyRevenue[] = ['month' => $m->format('M'), 'revenue' => (float) $rev];
        }

        return Inertia::render('admin/Dashboard', [
            'stats' => $stats,
            'topDoctors' => $topDoctors,
            'recentAppointments' => $recentAppointments,
            'monthlyRevenue' => $monthlyRevenue,
        ]);
    }

    // ══════════════════════ DOCTORS ══════════════════════════════

    public function doctors(Request $request): Response
    {
        $query = Doctor::with('category');
        if ($request->filled('search')) $query->where('name','like','%'.$request->search.'%');
        if ($request->filled('category')) $query->where('category_id', $request->category);
        $doctors = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = DoctorCategory::active()->orderBy('name')->get(['id','name','icon']);

        return Inertia::render('admin/Doctors', [
            'doctors' => [
                'data' => collect($doctors->items())->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'email' => $d->email,
                    'phone' => $d->phone,
                    'category' => $d->category?->name ?? '—',
                    'icon' => $d->category?->icon ?? '🩺',
                    'photo' => $d->photo ? asset($d->photo) : null,
                    'consultancy_fee' => (float) $d->consultancy_fee,
                    'is_active' => (bool) $d->is_active,
                ]),
                'links' => $doctors->linkCollection(),
                'from' => $doctors->firstItem(),
                'to' => $doctors->lastItem(),
                'total' => $doctors->total(),
            ],
            'categories' => $categories,
            'filters' => $request->only(['search','category']),
        ]);
    }

    public function createDoctor(): Response
    {
        $categories = DoctorCategory::active()->orderBy('name')->get(['id','name','icon']);
        return Inertia::render('admin/DoctorForm', ['categories' => $categories, 'doctor' => null]);
    }

    public function storeDoctor(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|unique:doctors,email',
            'password'        => 'required|min:6',
            'category_id'     => 'nullable|exists:doctor_categories,id',
            'consultancy_fee' => 'required|numeric|min:0',
            'bio'             => 'nullable|string|max:400',
            'phone'           => 'nullable|string|max:20',
            'gender'          => 'nullable|in:Male,Female,Other',
        ]);

        $doctor = Doctor::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'category_id'     => $request->category_id,
            'consultancy_fee' => $request->consultancy_fee,
            'bio'             => $request->bio,
            'phone'           => $request->phone,
            'gender'          => $request->gender,
            'is_active'       => true,
        ]);

        // Give every new doctor a placeholder avatar right away, styled by
        // gender if given — no manual upload or command needed.
        $doctor->update([
            'photo' => \App\Support\AvatarGenerator::saveFor($doctor->name, 'doctors', $doctor->id, $doctor->gender),
        ]);

        $this->ns->send($doctor->id, 'doctor',
            'Welcome to MediCare!',
            'Your doctor account has been created. Please log in.',
            'general'
        );

        return redirect()->route('admin.doctors')->with('success', 'Doctor added successfully.');
    }

    public function editDoctor(int $id): Response
    {
        $doctor     = Doctor::findOrFail($id);
        $categories = DoctorCategory::active()->orderBy('name')->get(['id','name','icon']);

        return Inertia::render('admin/DoctorForm', [
            'categories' => $categories,
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
                'phone' => $doctor->phone,
                'category_id' => $doctor->category_id,
                'consultancy_fee' => (float) $doctor->consultancy_fee,
                'bio' => $doctor->bio,
                'gender' => $doctor->gender,
                'is_active' => (bool) $doctor->is_active,
                'photo' => $doctor->photo ? asset($doctor->photo) : null,
            ],
        ]);
    }

    public function updateDoctor(Request $request, int $id)
    {
        $doctor = Doctor::findOrFail($id);
        $request->validate([
            'name'            => 'required|string|max:100',
            'email'           => 'required|email|unique:doctors,email,'.$id,
            'category_id'     => 'nullable|exists:doctor_categories,id',
            'consultancy_fee' => 'required|numeric|min:0',
            'bio'             => 'nullable|string|max:400',
            'phone'           => 'nullable|string|max:20',
            'gender'          => 'nullable|in:Male,Female,Other',
        ]);

        $data = $request->only(['name','email','category_id','consultancy_fee','bio','phone','gender']);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);

        // If gender changed and the doctor is still using an
        // auto-generated placeholder (not a real uploaded photo),
        // regenerate it to match the new gender.
        $genderChanged = $data['gender'] !== $doctor->gender;
        $isPlaceholder = $doctor->photo && str_contains($doctor->photo, "generated-{$doctor->id}.");
        if ($genderChanged && ($isPlaceholder || ! $doctor->photo)) {
            $data['photo'] = \App\Support\AvatarGenerator::saveFor($data['name'], 'doctors', $doctor->id, $data['gender']);
        }

        $doctor->update($data);

        return redirect()->route('admin.doctors')->with('success', 'Doctor updated.');
    }

    public function deleteDoctor(int $id)
    {
        $doctor = Doctor::findOrFail($id);

        // A soft-deleted doctor can no longer log in at all (Doctor's
        // default query scope excludes them) — so deleting one with active
        // appointments leaves those patients with a booking nobody can ever
        // fulfill, and no notification that it happened. Block it instead;
        // the admin needs to cancel or reassign those first.
        $activeCount = Appointment::where('doctor_id', $doctor->id)->where('status', 'active')->count();
        if ($activeCount > 0) {
            return back()->with('error',
                "Can't delete Dr. {$doctor->name} — they have {$activeCount} active appointment(s). Cancel or reassign those first.");
        }

        $doctor->delete();

        return back()->with('success', 'Doctor deleted.');
    }

    // Doctor Schedules
    public function doctorSchedule(int $id): Response
    {
        $doctor = Doctor::with('schedules','breaks','blockedDates')->findOrFail($id);

        return Inertia::render('admin/DoctorSchedule', [
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'schedules' => $doctor->schedules->map(fn ($s) => [
                    'day_of_week' => $s->day_of_week,
                    'start_time' => substr($s->start_time, 0, 5),
                    'end_time' => substr($s->end_time, 0, 5),
                ]),
                'breaks' => $doctor->breaks->map(fn ($b) => [
                    'id' => $b->id,
                    'day_of_week' => $b->day_of_week,
                    'start_time' => substr($b->start_time, 0, 5),
                    'end_time' => substr($b->end_time, 0, 5),
                ]),
                'blockedDates' => $doctor->blockedDates->map(fn ($bd) => [
                    'id' => $bd->id,
                    'blocked_date' => $bd->blocked_date?->format('Y-m-d'),
                    'reason' => $bd->reason,
                ]),
            ],
        ]);
    }

    public function saveDoctorSchedule(Request $request, int $id)
    {
        $doctor = Doctor::findOrFail($id);

        // Delete existing
        $doctor->schedules()->delete();

        $newSchedule = []; // day_of_week => ['start' => ..., 'end' => ...]
        if ($request->has('schedules')) {
            foreach ($request->schedules as $day => $sched) {
                if (!empty($sched['enabled'])) {
                    DoctorSchedule::create([
                        'doctor_id'   => $id,
                        'day_of_week' => $day,
                        'start_time'  => $sched['start'],
                        'end_time'    => $sched['end'],
                        'is_active'   => true,
                    ]);
                    $newSchedule[(int) $day] = $sched;
                }
            }
        }

        // A schedule template edit is routine and often just a correction —
        // unlike a holiday or a blocked date, it doesn't unambiguously mean
        // "not available", so this warns instead of auto-cancelling. The
        // admin sees exactly how many existing bookings no longer fit and
        // can decide what to do with them, rather than patients losing an
        // appointment because someone fixed a typo in office hours.
        $conflicts = Appointment::where('doctor_id', $id)
            ->where('status', 'active')
            ->where('appointment_date', '>=', now()->toDateString())
            ->get()
            ->filter(function ($appt) use ($newSchedule) {
                $dow = (int) $appt->appointment_date->dayOfWeek;
                if (! isset($newSchedule[$dow])) {
                    return true; // that day is no longer enabled at all
                }
                $slot = $newSchedule[$dow];

                return $appt->slot_start < $slot['start'] || $appt->slot_start >= $slot['end'];
            });

        $msg = 'Schedule saved.';
        if ($conflicts->count() > 0) {
            $msg .= " Note: {$conflicts->count()} existing appointment(s) fall outside the new schedule — they were NOT cancelled automatically, please review them.";
        }

        return back()->with('success', $msg);
    }

    // ══════════════════════ PATIENTS ═════════════════════════════

    public function patients(Request $request): Response
    {
        $query = Patient::query();
        if ($request->filled('search')) $query->where(function($q) use ($request) {
            $q->where('first_name','like','%'.$request->search.'%')
              ->orWhere('last_name','like','%'.$request->search.'%')
              ->orWhere('email','like','%'.$request->search.'%');
        });
        $patients = $query->orderBy('first_name')->paginate(15)->withQueryString();

        return Inertia::render('admin/Patients', [
            'patients' => [
                'data' => collect($patients->items())->map(fn ($p) => [
                    'id' => $p->id,
                    'full_name' => $p->full_name,
                    'email' => $p->email,
                    'contact' => $p->contact,
                    'gender' => $p->gender,
                    'blood_group' => $p->blood_group,
                    'photo' => $p->photo ? asset($p->photo) : null,
                ]),
                'links' => $patients->linkCollection(),
                'from' => $patients->firstItem(),
                'to' => $patients->lastItem(),
                'total' => $patients->total(),
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    public function patientDetail(int $id): Response
    {
        $patient = Patient::with('appointments.doctor','documents')->findOrFail($id);
        $appointments = Appointment::with('doctor.category','prescription')
            ->where('patient_id',$id)
            ->orderByDesc('appointment_date')
            ->get();

        return Inertia::render('admin/PatientDetail', [
            'patient' => [
                'id' => $patient->id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'full_name' => $patient->full_name,
                'email' => $patient->email,
                'contact' => $patient->contact,
                'gender' => $patient->gender,
                'blood_group' => $patient->blood_group,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'address' => $patient->address,
                'photo' => $patient->photo ? asset($patient->photo) : null,
                'documents' => $patient->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->name ?? 'Document',
                    'url' => $doc->url ?? null,
                ]),
            ],
            'appointments' => $appointments->map(fn ($a) => [
                'id' => $a->id,
                'status' => $a->status,
                'appointment_date' => $a->appointment_date?->format('d M Y'),
                'doctor_name' => $a->doctor?->name ?? '—',
                'doctor_category' => $a->doctor?->category?->name ?? '—',
                'doctor_photo' => $a->doctor?->photo ? asset($a->doctor->photo) : null,
                'has_prescription' => (bool) $a->prescription,
            ]),
        ]);
    }

    public function updatePatient(Request $request, int $id)
    {
        $patient = Patient::findOrFail($id);
        $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'required|email|unique:patients,email,'.$id,
            'contact'       => 'nullable|string|max:20',
            'gender'        => 'nullable|in:Male,Female,Other',
            'blood_group'   => 'nullable|string|max:5',
            'date_of_birth' => 'nullable|date',
            'address'       => 'nullable|string|max:300',
        ]);
        $patient->update($request->only([
            'first_name','last_name','email','contact',
            'gender','blood_group','date_of_birth','address'
        ]));
        return back()->with('success', 'Patient details updated successfully.');
    }

    public function resetPatientPassword(Request $request, int $id)
    {
        $request->validate([
            'new_password' => 'required|min:6|confirmed',
        ]);
        $patient = Patient::findOrFail($id);
        $patient->update([
            'password' => Hash::make($request->new_password),
        ]);
        return back()->with('success', 'Password for ' . $patient->full_name . ' has been reset successfully.');
    }

    // ══════════════════════ ADMIN MANAGEMENT ════════════════════════

    public function admins(): Response
    {
        $admins = StaffAdmin::where('role', 'admin')->orderBy('created_at')->get();

        return Inertia::render('admin/Admins', [
            'admins' => $admins->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'username' => $a->username,
                'is_active' => (bool) $a->is_active,
                'created_at' => $a->created_at?->format('d M Y'),
            ]),
        ]);
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:staff_admins,email',
            'username' => 'required|string|max:50|unique:staff_admins,username',
            'password' => 'required|min:6|confirmed',
            'gender'   => 'nullable|in:Male,Female,Other',
        ]);

        $admin = StaffAdmin::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role'      => 'admin',
            'gender'    => $request->gender,
            'is_active' => true,
        ]);

        $admin->update([
            'photo' => \App\Support\AvatarGenerator::saveFor($admin->name, 'admins', $admin->id, $admin->gender),
        ]);

        return back()->with('success', 'New admin account created successfully.');
    }

    public function updateAdmin(Request $request, int $id)
    {
        $admin = StaffAdmin::where('role', 'admin')->findOrFail($id);
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:staff_admins,email,'.$id,
            'username' => 'required|string|max:50|unique:staff_admins,username,'.$id,
            'password' => 'nullable|min:6|confirmed',
            'gender'   => 'nullable|in:Male,Female,Other',
        ]);

        $data = [
            'name'     => $request->name,
            'email'    => $request->email,
            'username' => $request->username,
            'gender'   => $request->gender,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $genderChanged = $data['gender'] !== $admin->gender;
        $isPlaceholder = $admin->photo && str_contains($admin->photo, "generated-{$admin->id}.");
        if ($genderChanged && ($isPlaceholder || ! $admin->photo)) {
            $data['photo'] = \App\Support\AvatarGenerator::saveFor($data['name'], 'admins', $admin->id, $data['gender']);
        }

        $admin->update($data);
        return back()->with('success', 'Admin account updated successfully.');
    }

    public function deleteAdmin(int $id)
    {
        $admins = StaffAdmin::where('role', 'admin')->count();
        if ($admins <= 1) {
            return back()->with('error', 'Cannot delete the last admin account.');
        }
        StaffAdmin::where('role', 'admin')->findOrFail($id)->delete();
        return back()->with('success', 'Admin account deleted.');
    }

    // ══════════════════════ MY PROFILE (current admin session) ═══
    // Separate from admins()/storeAdmin()/etc above, which manage OTHER
    // admin accounts — this is the currently logged-in admin's own page.

    public function myProfile(): Response
    {
        $adminId = session('admin_id');
        $admin = $adminId ? StaffAdmin::find($adminId) : null;

        return Inertia::render('admin/Profile', [
            'admin' => $admin ? [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'username' => $admin->username,
                'gender' => $admin->gender,
                'photo' => $admin->photo ? asset($admin->photo) : null,
                'has_custom_photo' => $admin->photo && ! $this->photos->isPlaceholder($admin->photo, $admin->id),
            ] : null,
            // True only for a session created before this account-linking
            // fix existed — asks them to re-login once to pick it up.
            'isStaleSession' => $adminId === null,
            'fallbackName' => session('admin_name', 'Administrator'),
            // Logged in via the .env ADMIN_USERNAME/ADMIN_PASSWORD path —
            // still fully editable, just a nudge toward setting a proper
            // password instead of relying on the .env default.
            'usingEnvLogin' => (bool) session('admin_via_env_login'),
        ]);
    }

    public function updateMyProfile(Request $request)
    {
        $adminId = session('admin_id');
        if (! $adminId) {
            return back()->with('error', 'Your session predates account-linking for the default login. Please log out and log back in once to activate it.');
        }

        $admin = StaffAdmin::findOrFail($adminId);
        $request->validate([
            'name'   => 'required|string|max:100',
            'email'  => 'required|email|unique:staff_admins,email,'.$adminId,
            'gender' => 'nullable|in:Male,Female,Other',
            'photo'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'gender']);
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $genderChanged = ($data['gender'] ?? null) !== $admin->gender;

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $data['photo'] = $this->photos->store($request->file('photo'), 'admins', 'admin', $admin->id, $admin->photo);
        } elseif ($genderChanged && ($this->photos->isPlaceholder($admin->photo, $admin->id) || ! $admin->photo)) {
            $data['photo'] = \App\Support\AvatarGenerator::saveFor($data['name'], 'admins', $admin->id, $data['gender'] ?? null);
        }

        $admin->update($data);
        // Keep the session-cached display name in sync so the sidebar
        // reflects a name change immediately, without needing to re-login.
        session(['admin_name' => $admin->name]);

        return back()->with('success', 'Profile updated successfully.');
    }

    /** Remove the admin's custom photo and reset it to a generated placeholder avatar. */
    public function deletePhoto()
    {
        $adminId = session('admin_id');
        if (! $adminId) {
            return back()->with('error', 'Your session predates account-linking for the default login. Please log out and log back in once to activate it.');
        }

        $admin = StaffAdmin::findOrFail($adminId);

        if (! $admin->photo || $this->photos->isPlaceholder($admin->photo, $admin->id)) {
            return back()->with('error', 'You do not have a custom photo to remove.');
        }

        $admin->update([
            'photo' => $this->photos->resetToPlaceholder($admin->name, 'admins', $admin->id, $admin->gender, $admin->photo),
        ]);

        return back()->with('success', 'Profile photo removed.');
    }

    // ══════════════════════ APPOINTMENTS ═════════════════════════

    public function appointments(Request $request): Response
    {
        $query = Appointment::with('patient','doctor.category','prescription');
        if ($request->filled('date'))   $query->where('appointment_date', $request->date);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('doctor')) $query->where('doctor_id', $request->doctor);
        $appointments = $query->orderByDesc('appointment_date')->orderBy('slot_start')->paginate(20)->withQueryString();
        $doctors = Doctor::orderBy('name')->get(['id','name']);

        return Inertia::render('admin/Appointments', [
            'appointments' => [
                'data' => collect($appointments->items())->map(fn ($a) => [
                    'id' => $a->id,
                    'status' => $a->status,
                    'appointment_date' => $a->appointment_date?->format('d M Y'),
                    'slot_start' => $a->slot_start ? substr($a->slot_start, 0, 5) : null,
                    'slot_end' => $a->slot_end ? substr($a->slot_end, 0, 5) : null,
                    'patient_name' => $a->patient?->full_name ?? '—',
                    'patient_photo' => $a->patient?->photo ? asset($a->patient->photo) : null,
                    'doctor_name' => $a->doctor?->name ?? '—',
                    'doctor_category' => $a->doctor?->category?->name ?? '—',
                    'doctor_photo' => $a->doctor?->photo ? asset($a->doctor->photo) : null,
                    'has_prescription' => (bool) $a->prescription,
                ]),
                'links' => $appointments->linkCollection(),
                'from' => $appointments->firstItem(),
                'to' => $appointments->lastItem(),
                'total' => $appointments->total(),
            ],
            'doctors' => $doctors,
            'filters' => $request->only(['date','status','doctor']),
        ]);
    }

    // ══════════════════════ REVENUE ══════════════════════════════

    public function revenue(Request $request): Response
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year,$mon] = explode('-', $month);

        $records = Appointment::with('patient','doctor.category')
            ->where('status','completed')
            ->whereYear('appointment_date', $year)
            ->whereMonth('appointment_date', $mon)
            ->orderBy('appointment_date')
            ->get();

        $totalRevenue = $records->sum(fn ($a) => $a->consultancy_fee ?? $a->doctor?->consultancy_fee ?? 0);

        // Per doctor breakdown
        $byDoctor = $records->groupBy('doctor_id')->map(function ($group) {
            $doctor = $group->first()->doctor;
            return [
                'doctor'   => $doctor->name,
                'count'    => $group->count(),
                'revenue'  => $group->sum(fn ($a) => $a->consultancy_fee ?? $doctor?->consultancy_fee ?? 0),
            ];
        })->values();

        return Inertia::render('admin/Revenue', [
            'records' => $records->map(fn ($a) => [
                'id' => $a->id,
                'appointment_date' => $a->appointment_date?->format('d M Y'),
                'patient_name' => $a->patient?->full_name ?? '—',
                'doctor_name' => $a->doctor?->name ?? '—',
                'doctor_category' => $a->doctor?->category?->name ?? '—',
                'fee' => (float) ($a->consultancy_fee ?? $a->doctor?->consultancy_fee ?? 0),
            ]),
            'totalRevenue' => (float) $totalRevenue,
            'byDoctor' => $byDoctor,
            'month' => $month,
        ]);
    }

    // ══════════════════════ CATEGORIES ═══════════════════════════

    public function categories(): Response
    {
        $categories = DoctorCategory::withCount('doctors')->orderBy('name')->get();
        $editId = request('edit');
        $editCategory = $editId ? DoctorCategory::find($editId) : null;

        return Inertia::render('admin/Categories', [
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'description' => $c->description,
                'is_active' => (bool) $c->is_active,
                'doctors_count' => $c->doctors_count,
            ]),
            'editCategory' => $editCategory ? [
                'id' => $editCategory->id,
                'name' => $editCategory->name,
                'icon' => $editCategory->icon,
                'description' => $editCategory->description,
                'is_active' => (bool) $editCategory->is_active,
            ] : null,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100|unique:doctor_categories,name', 'icon' => 'nullable|string|max:10']);
        DoctorCategory::create(['name' => $request->name, 'icon' => $request->icon ?? '🩺', 'description' => $request->description]);
        return back()->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, int $id)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:doctor_categories,name,'.$id,
            'icon'        => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
        ]);
        DoctorCategory::findOrFail($id)->update([
            'name'        => $request->name,
            'icon'        => $request->icon ?? '🩺',
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active'),
        ]);
        return redirect()->route('admin.categories')->with('success', 'Category updated.');
    }

    public function deleteCategory(int $id)
    {
        $cat = DoctorCategory::withCount('doctors')->findOrFail($id);
        if ($cat->doctors_count > 0) return back()->with('error', 'Cannot delete — '.$cat->doctors_count.' doctor(s) assigned.');
        $cat->delete();
        return back()->with('success', 'Category deleted.');
    }

    // ══════════════════════ HOLIDAYS ═════════════════════════════

    public function holidays(): Response
    {
        $holidays = HospitalHoliday::orderBy('holiday_date')->get();

        return Inertia::render('admin/Holidays', [
            'holidays' => $holidays->map(fn ($h) => [
                'id' => $h->id,
                'holiday_date' => $h->holiday_date?->format('Y-m-d'),
                'holiday_date_label' => $h->holiday_date?->format('d M Y (D)'),
                'name' => $h->name,
            ]),
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $request->validate(['holiday_date' => 'required|date|unique:hospital_holidays,holiday_date', 'name' => 'required|string|max:100']);
        $holiday = HospitalHoliday::create($request->only(['holiday_date', 'name']));

        // Declaring a holiday doesn't retroactively touch existing bookings
        // on its own — a patient who already booked that date would
        // otherwise be left showing up to an appointment that's quietly not
        // happening, with nobody having told them. Cancel those bookings
        // and notify the patients directly, right when the holiday is made.
        $affected = Appointment::with('patient', 'doctor')
            ->where('appointment_date', $holiday->holiday_date)
            ->where('status', 'active')
            ->get();

        foreach ($affected as $appt) {
            $appt->update(['status' => 'cancelled_by_admin']);
            if ($appt->patient) {
                $this->ns->send($appt->patient_id, 'patient',
                    'Appointment Cancelled — Hospital Holiday',
                    "Your appointment with Dr. {$appt->doctor?->name} on {$appt->appointment_date->format('d M Y')} has been cancelled — the hospital will be closed for {$holiday->name}. Please rebook for another date.",
                    'cancellation'
                );
            }
        }

        $msg = 'Holiday added.';
        if ($affected->count() > 0) {
            $msg .= " {$affected->count()} existing appointment(s) on this date were cancelled and the affected patients notified.";
        }

        return back()->with('success', $msg);
    }

    public function deleteHoliday(int $id)
    {
        HospitalHoliday::findOrFail($id)->delete();
        return back()->with('success', 'Holiday deleted.');
    }

    // ══════════════════════ FEEDBACK ═════════════════════════════

    public function feedback(): Response
    {
        $contacts = Contact::orderByDesc('created_at')->paginate(15);

        return Inertia::render('admin/Feedback', [
            'contacts' => [
                'data' => collect($contacts->items())->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'email' => $c->email,
                    'subject' => $c->subject ?? null,
                    'message' => $c->message,
                    'is_resolved' => (bool) $c->is_resolved,
                    'created_at' => $c->created_at?->format('d M Y, h:i A'),
                ]),
                'links' => $contacts->linkCollection(),
                'from' => $contacts->firstItem(),
                'to' => $contacts->lastItem(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    public function resolveContact(int $id)
    {
        Contact::findOrFail($id)->update(['is_resolved' => true]);
        return back()->with('success', 'Marked as resolved.');
    }

    // ── Live Search Suggest ───────────────────────────────────────
    public function doctorSuggest(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 1) return response()->json([]);
        $doctors = Doctor::with('category')
            ->where('is_active', true)
            ->where('name', 'like', "%{$q}%")
            ->limit(8)->get()
            ->map(fn($d) => [
                'id'       => $d->id,
                'name'     => $d->name,
                'category' => $d->category?->name ?? 'General',
                'icon'     => $d->category?->icon ?? '🩺',
                'photo'    => $d->photo ? asset($d->photo) : null,
            ]);
        return response()->json($doctors);
    }

    public function patientSuggest(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 1) return response()->json([]);
        $patients = Patient::where('first_name', 'like', "%{$q}%")
            ->orWhere('last_name',  'like', "%{$q}%")
            ->orWhere('email',      'like', "%{$q}%")
            ->orWhere('contact',    'like', "%{$q}%")
            ->limit(8)->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->full_name,
                'email' => $p->email,
                'phone' => $p->contact ?? '',
                'photo' => $p->photo ? asset($p->photo) : null,
            ]);
        return response()->json($patients);
    }

    // ── Settings ──────────────────────────────────────────────────

    public function settings(): Response
    {
        $settings = HospitalSetting::pluck('sval','skey');

        return Inertia::render('admin/Settings', [
            'settings' => [
                'hospital_name' => $settings['hospital_name'] ?? '',
                'hospital_address' => $settings['hospital_address'] ?? '',
                'hospital_phone' => $settings['hospital_phone'] ?? '',
                'hospital_email' => $settings['hospital_email'] ?? '',
                'slot_duration' => $settings['slot_duration'] ?? '15',
                'currency_symbol' => $settings['currency_symbol'] ?? '৳',
                'hospital_logo' => !empty($settings['hospital_logo']) ? asset($settings['hospital_logo']) : null,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'hospital_name'    => 'required|string|max:200',
            'hospital_address' => 'nullable|string',
            'hospital_phone'   => 'nullable|string|max:20',
            'hospital_email'   => 'nullable|email',
            'slot_duration'    => 'required|integer|in:10,15,20,30',
            'currency_symbol'  => 'nullable|string|max:5',
            'hospital_logo'    => 'nullable|image|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('hospital_logo') && $request->file('hospital_logo')->isValid()) {
            $logo = $request->file('hospital_logo');
            $ext  = $logo->extension(); // content-sniffed, not the client-supplied filename — see ProfilePhotoService::store()

            if ($ext === 'svg') {
                // SVG is XML and can carry <script>, event-handler attributes,
                // and javascript: URIs — none needed for a static logo, all of
                // them a stored-XSS vector if the file is ever opened directly.
                // Laravel's image/mimes validation above only confirms the
                // upload *is* an SVG, not that it's a *safe* one.
                $clean = SvgSanitizer::clean(file_get_contents($logo->getRealPath()));
                if ($clean === null) {
                    return back()->withErrors(['hospital_logo' => 'That SVG file could not be safely processed. Please try a different file.']);
                }
                $name = 'hospital-logo.svg';
                file_put_contents(public_path('images/'.$name), $clean);
            } else {
                $name = 'hospital-logo.'.$ext;
                $logo->move(public_path('images'), $name);
            }

            HospitalSetting::set('hospital_logo', 'images/' . $name . '?v=' . time());
        }

        // Handle logo removal
        if ($request->input('remove_logo') === '1') {
            HospitalSetting::set('hospital_logo', '');
            $oldLogo = glob(public_path('images/hospital-logo.*'));
            foreach ($oldLogo as $f) @unlink($f);
        }

        // Save all other settings
        $fields = ['hospital_name','hospital_address','hospital_phone','hospital_email','slot_duration','currency_symbol'];
        foreach ($fields as $key) {
            HospitalSetting::set($key, $request->input($key, ''));
        }

        return back()->with('success', 'Settings saved successfully.');
    }

    // ══════════════════════ AI HOSPITAL INSIGHTS ══════════════════

    public function aiInsights(): Response
    {
        return Inertia::render('admin/AiInsights');
    }

    public function generateAiInsights(Request $request)
    {
        // ── Collect comprehensive hospital data ───────────────────
        $now     = Carbon::now();
        $thisMonth = $now->format('Y-m');
        $lastMonth = $now->copy()->subMonth()->format('Y-m');
        $last3Months = [];
        for ($i = 2; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $rev = Appointment::where('status','completed')
                ->whereYear('appointment_date', $m->year)
                ->whereMonth('appointment_date', $m->month)
                ->join('doctors','appointments.doctor_id','=','doctors.id')
                ->sum(DB::raw('COALESCE(appointments.consultancy_fee, doctors.consultancy_fee)'));
            $cnt = Appointment::where('status','completed')
                ->whereYear('appointment_date', $m->year)
                ->whereMonth('appointment_date', $m->month)->count();
            $last3Months[] = ['month'=>$m->format('M Y'), 'revenue'=>$rev, 'appointments'=>$cnt];
        }

        // Specialty demand
        $specialtyDemand = DB::table('appointments')
            ->join('doctors','appointments.doctor_id','=','doctors.id')
            ->join('doctor_categories','doctors.category_id','=','doctor_categories.id')
            ->selectRaw('doctor_categories.name as specialty, COUNT(*) as total, SUM(CASE WHEN appointments.status="completed" THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN appointments.status IN ("cancelled_by_patient","cancelled_by_doctor","cancelled_by_admin") THEN 1 ELSE 0 END) as cancelled')
            ->groupBy('doctor_categories.id','doctor_categories.name')
            ->orderByDesc('total')
            ->get()->toArray();

        // Doctor stats
        $doctorStats = DB::table('appointments')
            ->join('doctors','appointments.doctor_id','=','doctors.id')
            ->selectRaw('doctors.id, doctors.name, COUNT(*) as total_appts, SUM(CASE WHEN appointments.status="completed" THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN appointments.status IN ("cancelled_by_patient","cancelled_by_doctor","cancelled_by_admin") THEN 1 ELSE 0 END) as cancelled, AVG(CASE WHEN doctor_ratings.rating IS NOT NULL THEN doctor_ratings.rating END) as avg_rating')
            ->leftJoin('doctor_ratings','appointments.id','=','doctor_ratings.appointment_id')
            ->groupBy('doctors.id','doctors.name')
            ->orderByDesc('total_appts')
            ->get()->toArray();

        // Busy days — grouped by day-of-week name. DAYNAME() is MySQL-only
        // and doesn't exist on SQLite (the driver the automated test suite
        // runs against, per phpunit.xml) — it would throw "no such
        // function: DAYNAME" the moment any test exercised this endpoint.
        // Grouping by the actual date is portable across both drivers;
        // turning that into a day name is plain PHP via Carbon instead of
        // a database-specific function.
        $busyDaysByDate = DB::table('appointments')
            ->selectRaw('appointment_date, COUNT(*) as count')
            ->where('status', 'completed')
            ->whereDate('appointment_date', '>=', $now->copy()->subDays(60))
            ->groupBy('appointment_date')
            ->get();

        $busyDayTotals = [];
        foreach ($busyDaysByDate as $row) {
            $dayName = \Carbon\Carbon::parse($row->appointment_date)->format('l');
            $busyDayTotals[$dayName] = ($busyDayTotals[$dayName] ?? 0) + $row->count;
        }
        arsort($busyDayTotals);
        $busyDays = collect($busyDayTotals)
            ->map(fn ($count, $day) => ['day_name' => $day, 'count' => $count])
            ->values()->all();

        // Inactive patients (completed visits but no appointment in 90 days)
        $inactivePatients = DB::table('patients')
            ->join('appointments','patients.id','=','appointments.patient_id')
            ->selectRaw('patients.id, COUNT(*) as total_visits, MAX(appointments.appointment_date) as last_visit')
            ->where('appointments.status','completed')
            ->groupBy('patients.id')
            ->havingRaw('MAX(appointments.appointment_date) < ?', [$now->copy()->subDays(90)->format('Y-m-d')])
            ->count();

        // Cancellation rate
        $totalAppts     = Appointment::count();
        $cancelledAppts = Appointment::whereIn('status', ['cancelled_by_patient', 'cancelled_by_doctor', 'cancelled_by_admin'])->count();
        $cancelRate     = $totalAppts > 0 ? round(($cancelledAppts / $totalAppts) * 100, 1) : 0;

        // Doctors with no appointments in 30 days
        $inactiveDoctors = Doctor::whereDoesntHave('appointments', function($q) use ($now) {
            $q->whereDate('appointment_date', '>=', $now->copy()->subDays(30));
        })->count();

        // Bed utilization
        $totalBeds     = HospitalBed::count();
        $occupiedBeds  = HospitalBed::where('status','occupied')->count();
        $bedUtilization = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

        // Doctor count per specialty
        $doctorsPerSpecialty = DB::table('doctors')
            ->join('doctor_categories','doctors.category_id','=','doctor_categories.id')
            ->selectRaw('doctor_categories.name as specialty, COUNT(*) as doctor_count')
            ->groupBy('doctor_categories.id','doctor_categories.name')
            ->orderByDesc('doctor_count')
            ->get()->toArray();

        // How often doctors are overriding an AI safety-check flag, and
        // how many referrals are happening — both genuinely new signal an
        // admin should be able to see, not previously surfaced here at all.
        $prescriptions30d = Prescription::where('created_at', '>=', $now->copy()->subDays(30))->count();
        // Guarded specifically: safety_check_override only exists if that
        // migration has been run. This is one optional stat among many, so
        // it must not be able to take down the entire insights generation
        // if it's missing — same principle as the vitals guards elsewhere.
        $safetyOverrides30d = 0;
        try {
            $safetyOverrides30d = Prescription::whereNotNull('safety_check_override')
                ->where('created_at', '>=', $now->copy()->subDays(30))->count();
        } catch (\Throwable $e) {
            // Swallow and continue — rate below just reads as 0%.
        }
        $safetyOverrideRate = $prescriptions30d > 0 ? round(($safetyOverrides30d / $prescriptions30d) * 100, 1) : 0;
        $referrals30d = Referral::where('created_at', '>=', $now->copy()->subDays(30))->count();

        $hospitalData = [
            'overview' => [
                'total_doctors'       => Doctor::count(),
                'total_patients'      => Patient::count(),
                'total_appointments'  => $totalAppts,
                'cancellation_rate'   => $cancelRate . '%',
                'inactive_patients_90days' => $inactivePatients,
                'inactive_doctors_30days'  => $inactiveDoctors,
                'bed_utilization'     => $bedUtilization . '%',
                'total_beds'          => $totalBeds,
                'occupied_beds'       => $occupiedBeds,
                'prescriptions_30days'      => $prescriptions30d,
                'safety_overrides_30days'   => $safetyOverrides30d,
                'safety_override_rate'      => $safetyOverrideRate . '%',
                'referrals_30days'          => $referrals30d,
            ],
            'revenue_last_3_months' => $last3Months,
            'specialty_demand'      => $specialtyDemand,
            'doctor_performance'    => array_slice($doctorStats, 0, 10),
            'busiest_days'          => $busyDays,
            'doctors_per_specialty' => $doctorsPerSpecialty,
        ];

        $dataJson = json_encode($hospitalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are a hospital management AI consultant. Analyze this hospital's operational data and provide specific, actionable insights for the admin.

HOSPITAL DATA:
{$dataJson}

RULES:
- Be specific — mention actual numbers, doctor names, specialty names from the data
- Focus on actionable recommendations the admin can implement today
- Prioritize by impact — most important first
- Use "monitoring" or "follow-up" diagnoses as active conditions
- For revenue trends: compare months and identify direction
- Identify both problems AND opportunities
- Keep each insight concise — 1-2 sentences max per item
- On safety_override_rate: a high rate is not automatically bad — it can mean doctors are appropriately using their own clinical judgment against an overly-cautious AI flag, or it can mean something worth reviewing. Use your own judgment on whether the specific number here warrants a flag; do not assume either direction by default.
- On referrals_30days: a rising or high number can reflect appropriate specialist routing, not necessarily a problem — only raise it as a concern if the data gives an actual reason to (e.g. concentrated in one doctor or specialty), not just because the number itself is nonzero.

Respond ONLY with this JSON — no markdown, no explanation:
{
  "executive_summary": "2-3 sentence overall hospital health assessment",
  "critical_alerts": [
    {"title": "alert title", "detail": "specific detail with numbers", "action": "exact action to take"}
  ],
  "revenue_insights": [
    {"title": "insight title", "detail": "specific detail", "action": "recommendation"}
  ],
  "operational_insights": [
    {"title": "insight title", "detail": "specific detail", "action": "recommendation"}
  ],
  "growth_opportunities": [
    {"title": "opportunity title", "detail": "specific detail with numbers", "action": "how to capitalize"}
  ],
  "quick_wins": ["specific quick action 1", "specific quick action 2", "specific quick action 3", "specific quick action 4"]
}
PROMPT;

        $apiKey = config('services.groq.key');
        if (empty($apiKey) || $apiKey === 'your-groq-key-here') {
            return response()->json(['error' => 'API Not Connected — GROQ_API_KEY is not set in .env'], 503);
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Authorization' => 'Bearer '.$apiKey, 'Content-Type' => 'application/json'])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'                 => 'openai/gpt-oss-120b',
                    'temperature'           => 0,
                    'max_completion_tokens' => 1500,
                    'reasoning_effort'      => 'low',
                    'include_reasoning'     => false,
                    'response_format'       => ['type' => 'json_object'],
                    'messages'              => [
                        ['role' => 'system', 'content' => 'You are a hospital management AI consultant. Always respond with valid JSON only. No markdown, no explanation, no extra text.'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                ]);

            if ($response->status() === 429) {
                sleep(5);
                $response = Http::timeout(60)
                    ->withHeaders(['Authorization' => 'Bearer '.$apiKey, 'Content-Type' => 'application/json'])
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model'=>'openai/gpt-oss-120b','temperature'=>0,'max_completion_tokens'=>1500,
                        'reasoning_effort'=>'low','include_reasoning'=>false,'response_format'=>['type'=>'json_object'],
                        'messages'=>[
                            ['role'=>'system','content'=>'You are a hospital management AI consultant. Respond with valid JSON only.'],
                            ['role'=>'user','content'=>$prompt],
                        ],
                    ]);
            }

            if (!$response->successful()) {
                return response()->json(['error' => 'API error: '.$response->status()], 503);
            }

            $raw = trim($response->json()['choices'][0]['message']['content'] ?? '');
            $raw = preg_replace('/^```json\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/i', '', $raw);
            $start = strpos($raw, '{'); $end = strrpos($raw, '}');
            if ($start !== false && $end !== false) $raw = substr($raw, $start, $end - $start + 1);
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'AI returned invalid response. Try again.'], 503);
            }

            $data['critical_alerts'] = $data['critical_alerts'] ?? [];
            $data['revenue_insights'] = $data['revenue_insights'] ?? [];
            $data['operational_insights'] = $data['operational_insights'] ?? [];
            $data['growth_opportunities'] = $data['growth_opportunities'] ?? [];
            $data['quick_wins'] = $data['quick_wins'] ?? [];

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Connection failed: '.$e->getMessage()], 503);
        }
    }
}
