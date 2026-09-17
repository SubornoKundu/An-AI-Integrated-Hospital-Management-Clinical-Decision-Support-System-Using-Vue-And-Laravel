<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorCategory;
use App\Models\DoctorRating;
use App\Models\HospitalSetting;
use App\Models\Notification;
use App\Models\PatientDocument;
use App\Models\PatientFavourite;
use App\Models\Prescription;
use App\Models\Referral;
use App\Services\NotificationService;
use App\Services\ProfilePhotoService;
use App\Services\SlotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected SlotService $slots;

    protected NotificationService $ns;

    protected ProfilePhotoService $photos;

    public function __construct(SlotService $slots, NotificationService $ns, ProfilePhotoService $photos)
    {
        $this->slots = $slots;
        $this->ns = $ns;
        $this->photos = $photos;
    }

    private function patient()
    {
        return Auth::guard('patient')->user();
    }

    /** Shape a single Appointment model into a stable, frontend-friendly array. */
    private function appointmentCard(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'status' => $a->status,
            'appointment_date' => $a->appointment_date?->format('Y-m-d'),
            'appointment_date_label' => $a->appointment_date?->format('d M Y (D)'),
            'slot_start' => $a->slot_start,
            'slot_end' => $a->slot_end,
            'doctor' => $a->doctor ? [
                'id' => $a->doctor->id,
                'name' => $a->doctor->name,
                'category' => $a->doctor->category?->name ?? 'Specialist',
                'icon' => $a->doctor->category?->icon ?? '🩺',
                'photo' => $a->doctor->photo ? asset($a->doctor->photo) : null,
                'consultancy_fee' => (float) ($a->consultancy_fee ?? $a->doctor->consultancy_fee),
            ] : null,
            'has_prescription' => (bool) $a->relationLoaded('prescription') ? $a->prescription !== null : false,
            'rating' => $a->relationLoaded('rating') && $a->rating ? [
                'rating' => $a->rating->rating,
                'review' => $a->rating->review,
            ] : null,
        ];
    }

    // Dashboard
    public function dashboardFull(): Response
    {
        $patient = $this->patient();
        $upcoming = Appointment::with('doctor.category')
            ->where('patient_id', $patient->id)->where('status', 'active')
            ->where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date')->orderBy('slot_start')->take(5)->get();
        $appointments = Appointment::with('doctor.category', 'prescription', 'rating')
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')->take(10)->get();
        $stats = [
            'total' => Appointment::where('patient_id', $patient->id)->count(),
            'active' => Appointment::where('patient_id', $patient->id)->where('status', 'active')->count(),
            'completed' => Appointment::where('patient_id', $patient->id)->where('status', 'completed')->count(),
        ];

        // Follow-ups the doctor asked for that the patient hasn't come back
        // for yet — "hasn't come back" means no later appointment with that
        // SAME doctor since. A follow_up_date is otherwise just a field
        // nobody ever acts on; there's no scheduling/reminder infrastructure
        // in this app at all, so this has to be something shown in-app the
        // moment they log in, not an email/SMS that needs a cron job.
        $pendingFollowUps = Prescription::with('appointment', 'doctor')
            ->where('patient_id', $patient->id)
            ->whereNotNull('follow_up_date')
            ->get()
            ->filter(fn ($rx) => $rx->appointment && ! Appointment::where('patient_id', $patient->id)
                ->where('doctor_id', $rx->doctor_id)
                ->where('appointment_date', '>', $rx->appointment->appointment_date)
                ->whereIn('status', ['active', 'completed'])
                ->exists())
            ->map(fn ($rx) => [
                'prescription_id' => $rx->id,
                'doctor_name' => $rx->doctor?->name ?? 'Unknown',
                'diagnosis' => $rx->diagnosis,
                'follow_up_date' => $rx->follow_up_date->format('d M Y'),
                'is_overdue' => $rx->follow_up_date->isPast(),
            ])
            ->sortBy('follow_up_date')->values();

        return Inertia::render('patient/Dashboard', [
            'patient' => ['id' => $patient->id, 'name' => $patient->full_name, 'first_name' => $patient->first_name],
            'stats' => $stats,
            'upcoming' => $upcoming->map(fn ($a) => $this->appointmentCard($a)),
            'recent' => $appointments->map(fn ($a) => $this->appointmentCard($a)),
            'pendingFollowUps' => $pendingFollowUps,
        ]);
    }

    // Book Step 1 — choose specialty
    public function bookStep1(): Response
    {
        $categories = DoctorCategory::where('is_active', 1)->withCount('doctors')->having('doctors_count', '>', 0)->orderBy('name')->get();

        return Inertia::render('patient/book/Step1', [
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'icon' => $c->icon, 'doctors_count' => $c->doctors_count,
            ]),
        ]);
    }

    // Book Step 2 — choose doctor (handles both page & AJAX)
    public function bookStep2(Request $request): Response
    {
        $request->validate(['category_id' => 'required|exists:doctor_categories,id']);

        $category = DoctorCategory::findOrFail($request->category_id);
        $doctors = Doctor::with('category')
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->where('category_id', $request->category_id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('patient/book/Step2', [
            'category' => ['id' => $category->id, 'name' => $category->name, 'icon' => $category->icon],
            'doctors' => $doctors->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'bio' => $d->bio, 'phone' => $d->phone,
                'photo' => $d->photo ? asset($d->photo) : null,
                'consultancy_fee' => (float) $d->consultancy_fee,
                'avg_rating' => round($d->ratings_avg_rating ?? 0, 1), 'rating_count' => $d->ratings_count,
            ]),
        ]);
    }

    // Book Step 3 — choose date & slot
    public function bookStep3(Request $request): Response
    {
        $request->validate(['doctor_id' => 'required|exists:doctors,id']);
        $doctor = Doctor::with('category', 'schedules')->findOrFail($request->doctor_id);
        $date = $request->get('date', now()->toDateString());
        $slots = $this->slots->getSlots($doctor->id, $date);
        $slotMinutes = (int) HospitalSetting::get('slot_duration', '15');

        return Inertia::render('patient/book/Step3', [
            'doctor' => [
                'id' => $doctor->id, 'name' => $doctor->name, 'bio' => $doctor->bio, 'phone' => $doctor->phone,
                'category_id' => $doctor->category_id,
                'category' => $doctor->category?->name ?? 'Specialist',
                'icon' => $doctor->category?->icon ?? '🩺',
                'photo' => $doctor->photo ? asset($doctor->photo) : null,
                'consultancy_fee' => (float) $doctor->consultancy_fee,
            ],
            'date' => $date,
            'slots' => $slots,
            'slotMinutes' => $slotMinutes,
        ]);
    }

    // Confirm Booking
    public function bookConfirm(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'slot_start' => 'required',
        ]);

        $patient = $this->patient();
        $date = $request->appointment_date;
        $slotStart = $request->slot_start;

        // Without the transaction + lockForUpdate, two concurrent requests for
        // the same doctor+date+slot could both pass the "is it free?" check
        // below before either one's Appointment::create() runs — a real
        // double-booking race, not a theoretical one, and exactly the
        // "concurrent appointment creation" scenario the brief's load-testing
        // section calls out. Locking the doctor row serializes booking
        // attempts for that doctor specifically; other doctors are unaffected.
        $result = DB::transaction(function () use ($patient, $date, $slotStart, $request) {
            $doctor = Doctor::where('id', $request->doctor_id)->lockForUpdate()->firstOrFail();

            $allSlots = $this->slots->getSlots($doctor->id, $date);
            if (isset($allSlots['holiday'])) {
                return ['error' => 'Hospital holiday — no bookings on this date.'];
            }
            $slot = collect($allSlots)->firstWhere('start', $slotStart);
            if (! $slot || $slot['booked']) {
                return ['error' => 'This slot is no longer available. Please choose another.'];
            }

            try {
                Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'appointment_date' => $date,
                    'slot_start' => $slotStart,
                    'slot_end' => $slot['end'],
                    'consultancy_fee' => $doctor->consultancy_fee,
                    'status' => 'active',
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // The application-level check above is the primary defense
                // and should catch this before it ever reaches here — this
                // is the database-level constraint (added specifically as a
                // backstop for exactly this kind of gap) actually doing its
                // job. Same friendly message either way, not a raw 500.
                return ['error' => 'This slot is no longer available. Please choose another.'];
            }

            Referral::where('patient_id', $patient->id)
                ->where('to_category_id', $doctor->category_id)
                ->where('status', 'pending')
                ->update(['status' => 'booked']);

            $this->ns->send($patient->id, 'patient', 'Appointment Confirmed',
                "Your appointment with {$doctor->name} on {$date} at {$slotStart} is confirmed.", 'booking');
            $this->ns->send($doctor->id, 'doctor', 'New Appointment',
                "{$patient->full_name} booked on {$date} at {$slotStart}.", 'booking');

            return ['doctor' => $doctor];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }
        $doctor = $result['doctor'];

        return redirect()->route('patient.appointments')
            ->with('success', "Appointment booked with {$doctor->name} on {$date} at {$slotStart}!");
    }

    // AJAX: Get available slots
    public function getSlots(Request $request)
    {
        $request->validate(['doctor_id' => 'required', 'date' => 'required|date']);

        return response()->json($this->slots->getSlots($request->doctor_id, $request->date));
    }

    // All Appointments
    public function appointments(Request $request): Response
    {
        $patient = $this->patient();
        $query = Appointment::with('doctor.category', 'prescription', 'rating')
            ->where('patient_id', $patient->id);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('doctor')) {
            $query->where('doctor_id', $request->doctor);
        }
        $appointments = $query->orderByDesc('appointment_date')->orderByDesc('slot_start')->paginate(5)
            ->withQueryString()
            ->through(fn ($a) => $this->appointmentCard($a));
        $doctors = Doctor::orderBy('name')->get(['id', 'name']);

        return Inertia::render('patient/Appointments', [
            'appointments' => $appointments,
            'doctors' => $doctors,
            'filters' => $request->only(['status', 'doctor']),
        ]);
    }

    // Cancel Appointment
    public function cancelAppointment(Request $request, int $id)
    {
        $patient = $this->patient();
        $appointment = Appointment::where('id', $id)->where('patient_id', $patient->id)
            ->where('status', 'active')->firstOrFail();
        $appointment->update(['status' => 'cancelled_by_patient']);
        $this->ns->send($appointment->doctor_id, 'doctor', 'Appointment Cancelled',
            "{$patient->full_name} cancelled their appointment on {$appointment->appointment_date->format('d M Y')}.",
            'cancellation');

        return back()->with('success', 'Appointment cancelled successfully.');
    }

    // Rate Doctor
    public function rateDoctor(Request $request, int $appointmentId)
    {
        $request->validate(['rating' => 'required|integer|min:1|max:5', 'review' => 'nullable|string|max:500']);
        $patient = $this->patient();
        $appointment = Appointment::where('id', $appointmentId)
            ->where('patient_id', $patient->id)->where('status', 'completed')->firstOrFail();
        DoctorRating::updateOrCreate(
            ['appointment_id' => $appointment->id],
            ['doctor_id' => $appointment->doctor_id, 'patient_id' => $patient->id,
                'rating' => $request->rating, 'review' => $request->review]
        );

        return back()->with('success', 'Thank you for your feedback!');
    }

    // ── Download Invoice ─────────────────────────────────────────
    public function downloadInvoice(int $id)
    {
        $patient = $this->patient();
        $appointment = Appointment::with('doctor.category', 'prescription')
            ->where('id', $id)->where('patient_id', $patient->id)->firstOrFail();
        $hName = HospitalSetting::get('hospital_name', 'MediCare Hospital');
        $hAddress = HospitalSetting::get('hospital_address', 'Dhaka, Bangladesh');
        $hPhone = HospitalSetting::get('hospital_phone', '');
        $hEmail = HospitalSetting::get('hospital_email', '');

        return Pdf::loadView('patient.invoice_dompdf',
            compact('appointment', 'patient', 'hName', 'hAddress', 'hPhone', 'hEmail'))
            ->setPaper('a4', 'portrait')
            ->stream('invoice-'.str_pad($id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    // ── Download Prescription ────────────────────────────────────
    public function downloadPrescription(int $id)
    {
        $patient = $this->patient();
        $appointment = Appointment::with('doctor.category', 'prescription')
            ->where('id', $id)->where('patient_id', $patient->id)
            ->whereHas('prescription')->firstOrFail();
        $hName = HospitalSetting::get('hospital_name', 'MediCare Hospital');
        $hAddress = HospitalSetting::get('hospital_address', 'Dhaka, Bangladesh');
        $hPhone = HospitalSetting::get('hospital_phone', '');

        return Pdf::loadView('patient.prescription_dompdf',
            compact('appointment', 'patient', 'hName', 'hAddress', 'hPhone'))
            ->setPaper('a4', 'portrait')
            ->stream('prescription-'.str_pad($id, 6, '0', STR_PAD_LEFT).'.pdf');
    }

    // ── Invoices list ─────────────────────────────────────────────
    public function invoices(): Response
    {
        $patient = $this->patient();
        $appointments = Appointment::with('doctor.category')
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->get();

        return Inertia::render('patient/Invoices', [
            'appointments' => $appointments->map(fn (Appointment $a) => [
                'id' => $a->id,
                'appointment_date' => $a->appointment_date->format('d M Y'),
                'status' => $a->status,
                'fee' => (float) ($a->consultancy_fee ?? $a->doctor->consultancy_fee),
                'doctor' => [
                    'name' => $a->doctor->name,
                    'category' => $a->doctor->category?->name ?? 'General Medicine',
                    'icon' => $a->doctor->category?->icon ?? '🩺',
                    'photo' => $a->doctor->photo ? asset($a->doctor->photo) : null,
                ],
            ]),
        ]);
    }

    // ── Prescriptions list ───────────────────────────────────────
    public function prescriptions(): Response
    {
        $patient = $this->patient();
        $appointments = Appointment::with('doctor.category', 'prescription')
            ->where('patient_id', $patient->id)
            ->whereHas('prescription')
            ->orderByDesc('appointment_date')
            ->get();

        return Inertia::render('patient/Prescriptions', [
            'appointments' => $appointments->map(fn (Appointment $a) => [
                'id' => $a->id,
                'appointment_date' => $a->appointment_date->format('d M Y'),
                'diagnosis' => $a->prescription->diagnosis,
                'doctor' => [
                    'name' => $a->doctor->name,
                    'category' => $a->doctor->category?->name ?? 'General Medicine',
                    'icon' => $a->doctor->category?->icon ?? '🩺',
                    'photo' => $a->doctor->photo ? asset($a->doctor->photo) : null,
                ],
            ]),
        ]);
    }

    // Favourites
    public function favourites(): Response
    {
        $doctors = $this->patient()->favouriteDoctors()->with('category')
            ->withAvg('ratings', 'rating')->withCount('ratings')->get();

        return Inertia::render('patient/Favourites', [
            'doctors' => $doctors->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name,
                'category' => $d->category?->name ?? 'Specialist', 'icon' => $d->category?->icon ?? '🩺',
                'photo' => $d->photo ? asset($d->photo) : null,
                'consultancy_fee' => (float) $d->consultancy_fee,
                'avg_rating' => round($d->ratings_avg_rating ?? 0, 1), 'rating_count' => $d->ratings_count,
            ]),
        ]);
    }

    public function toggleFavourite(int $doctorId)
    {
        $patient = $this->patient();
        $exists = PatientFavourite::where('patient_id', $patient->id)->where('doctor_id', $doctorId)->first();
        if ($exists) {
            $exists->delete();

            return back()->with('success', 'Removed from favourites.');
        }
        PatientFavourite::create(['patient_id' => $patient->id, 'doctor_id' => $doctorId]);

        return back()->with('success', 'Added to favourites!');
    }

    // Notifications
    public function notifications(): Response
    {
        $patient = $this->patient();
        $items = Notification::where('user_id', $patient->id)->where('user_role', 'patient')
            ->orderByDesc('created_at')->paginate(20)
            ->through(fn ($n) => [
                'id' => $n->id, 'title' => $n->title, 'message' => $n->message,
                'type' => $n->type, 'is_read' => (bool) $n->is_read, 'link' => $n->link,
                'created_at' => $n->created_at->diffForHumans(),
            ]);
        Notification::where('user_id', $patient->id)->where('user_role', 'patient')->update(['is_read' => true]);

        return Inertia::render('patient/Notifications', ['items' => $items]);
    }

    // Profile
    public function profile(): Response
    {
        $patient = $this->patient();

        return Inertia::render('patient/Profile', [
            'patient' => [
                'first_name' => $patient->first_name, 'last_name' => $patient->last_name,
                'email' => $patient->email, 'contact' => $patient->contact, 'gender' => $patient->gender,
                'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                'blood_group' => $patient->blood_group, 'address' => $patient->address,
                'known_allergies' => $patient->known_allergies,
                'chronic_conditions' => $patient->chronic_conditions,
                'current_medications' => $patient->current_medications,
                'family_medical_history' => $patient->family_medical_history,
                'photo' => $patient->photo ? asset($patient->photo) : null,
                'has_custom_photo' => $patient->photo && ! $this->photos->isPlaceholder($patient->photo, $patient->id),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $patient = $this->patient();
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'contact' => 'required|string|max:15',
            'date_of_birth' => 'nullable|date',
            'blood_group' => 'nullable|string|max:5',
            'address' => 'nullable|string|max:500',
            'known_allergies' => 'nullable|string|max:1000',
            'chronic_conditions' => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
            'family_medical_history' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $data = $request->only([
            'first_name', 'last_name', 'contact', 'date_of_birth', 'blood_group', 'address',
            'known_allergies', 'chronic_conditions', 'current_medications', 'family_medical_history',
        ]);
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $data['password'] = Hash::make($request->password);
        }
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $data['photo'] = $this->photos->store($request->file('photo'), 'patients', 'patient', $patient->id, $patient->photo);
        }
        $patient->update($data);

        return back()->with('success', 'Profile updated successfully!');
    }

    /** Remove the patient's custom photo and reset it to a generated placeholder avatar. */
    public function deletePhoto()
    {
        $patient = $this->patient();

        if (! $patient->photo || $this->photos->isPlaceholder($patient->photo, $patient->id)) {
            return back()->with('error', 'You do not have a custom photo to remove.');
        }

        $patient->update([
            'photo' => $this->photos->resetToPlaceholder($patient->full_name, 'patients', $patient->id, $patient->gender, $patient->photo),
        ]);

        return back()->with('success', 'Profile photo removed.');
    }

    // Referrals
    public function referrals(): Response
    {
        $patient = $this->patient();
        $referrals = Referral::with('fromDoctor', 'toCategory', 'appointment')
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(fn ($r) => [
                'id' => $r->id, 'reason' => $r->reason, 'notes' => $r->notes, 'status' => $r->status,
                'from_doctor' => $r->fromDoctor?->name,
                'to_category' => $r->toCategory ? ['name' => $r->toCategory->name, 'icon' => $r->toCategory->icon, 'id' => $r->toCategory->id] : null,
                'created_at' => $r->created_at->format('d M Y'),
            ]);

        return Inertia::render('patient/Referrals', ['referrals' => $referrals]);
    }

    // Chat
    public function chat(int $appointmentId): Response
    {
        $patient = $this->patient();
        $appointment = Appointment::with('doctor.category')
            ->where('id', $appointmentId)->where('patient_id', $patient->id)->firstOrFail();
        $messages = $appointment->chatMessages()->orderBy('sent_at')->get();
        $appointment->chatMessages()->where('sender_role', 'doctor')->update(['is_read' => true]);

        return Inertia::render('patient/Chat', [
            'appointment' => [
                'id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date?->format('d M Y'),
                'slot_start' => $appointment->slot_start,
                'slot_end' => $appointment->slot_end,
                'doctor' => $appointment->doctor ? [
                    'name' => $appointment->doctor->name,
                    'phone' => $appointment->doctor->phone,
                    'category' => $appointment->doctor->category?->name ?? 'Specialist',
                    'icon' => $appointment->doctor->category?->icon ?? '🩺',
                    'photo' => $appointment->doctor->photo ? asset($appointment->doctor->photo) : null,
                    'is_online' => $appointment->doctor->last_seen_at
                        && $appointment->doctor->last_seen_at->gt(now()->subMinutes(2)),
                ] : null,
            ],
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_role' => $m->sender_role,
                'message' => $m->message,
                'time' => $m->sent_at ? \Carbon\Carbon::parse($m->sent_at)->format('h:i A') : '',
            ]),
        ]);
    }

    public function sendChat(Request $request, int $appointmentId)
    {
        $request->validate(['message' => 'required|string|max:1000']);
        $patient = $this->patient();
        $appointment = Appointment::where('id', $appointmentId)->where('patient_id', $patient->id)->firstOrFail();
        $appointment->chatMessages()->create([
            'sender_role' => 'patient', 'sender_id' => $patient->id, 'message' => $request->message,
        ]);
        $this->ns->send($appointment->doctor_id, 'doctor', 'New Message from Patient',
            "{$patient->full_name} sent you a message.", 'chat');

        return back();
    }

    // Search
    public function search(Request $request): Response
    {
        $doctors = collect();
        $categories = DoctorCategory::where('is_active', 1)->orderBy('name')->get();
        if ($request->filled('q') || $request->filled('category')) {
            $query = Doctor::with('category')->withAvg('ratings', 'rating')->withCount('ratings')->where('is_active', true);
            if ($request->filled('q')) {
                $q = $request->q;
                $query->where(fn ($qb) => $qb->where('name', 'like', "%{$q}%")->orWhere('bio', 'like', "%{$q}%"));
            }
            if ($request->filled('category')) {
                $query->where('category_id', $request->category);
            }
            $doctors = $query->get();
        }

        return Inertia::render('patient/Search', [
            'doctors' => $doctors->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'bio' => $d->bio, 'phone' => $d->phone,
                'category' => $d->category?->name ?? 'General', 'icon' => $d->category?->icon ?? '🩺',
                'photo' => $d->photo ? asset($d->photo) : null,
                'consultancy_fee' => (float) $d->consultancy_fee,
                'avg_rating' => round($d->ratings_avg_rating ?? 0, 1), 'rating_count' => $d->ratings_count,
            ])->values(),
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon]),
            'filters' => $request->only(['q', 'category']),
        ]);
    }

    // Doctor Live Search Suggest
    public function doctorSuggest(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 1) {
            return response()->json([]);
        }
        $doctors = Doctor::with('category')
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            })
            ->limit(8)->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'category' => $d->category?->name ?? 'General',
                'icon' => $d->category?->icon ?? '🩺',
                'photo' => $d->photo ? asset($d->photo) : null,
                'fee' => number_format($d->consultancy_fee, 0),
            ]);

        return response()->json($doctors);
    }

    // Health Card
    public function healthCard(): Response
    {
        $patient = $this->patient();
        $lastVisit = Appointment::with('doctor', 'prescription')
            ->where('patient_id', $patient->id)->where('status', 'completed')
            ->latest('appointment_date')->first();
        $totalVisits = Appointment::where('patient_id', $patient->id)->where('status', 'completed')->count();

        return Inertia::render('patient/HealthCard', [
            'patient' => [
                'name' => $patient->full_name, 'id' => $patient->id, 'gender' => $patient->gender,
                'blood_group' => $patient->blood_group, 'date_of_birth' => $patient->date_of_birth?->format('d M Y'),
                'age' => $patient->age, 'contact' => $patient->contact, 'email' => $patient->email,
            ],
            'lastVisit' => $lastVisit ? [
                'date' => $lastVisit->appointment_date->format('d M Y'),
                'doctor' => $lastVisit->doctor?->name,
            ] : null,
            'totalVisits' => $totalVisits,
        ]);
    }

    // Health Documents (lab reports, X-rays, etc.)
    public function documents(): Response
    {
        $patient = $this->patient();

        $documents = PatientDocument::where('patient_id', $patient->id)
            ->with('appointment:id,appointment_date')
            ->latest()
            ->get()
            ->map(fn ($d) => [
                'id'               => $d->id,
                'file_name'        => $d->file_name,
                'doc_type'         => $d->doc_type,
                'notes'            => $d->notes,
                'file_type'        => $d->file_type,
                'appointment_date' => $d->appointment?->appointment_date?->format('d M Y'),
                'uploaded_at'      => $d->created_at->format('d M Y'),
            ]);

        return Inertia::render('patient/Documents', [
            'documents' => $documents,
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $patient = $this->patient();

        $request->validate([
            'file'            => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'doc_type'        => 'required|in:lab_report,xray,prescription,other',
            'notes'           => 'nullable|string|max:300',
            'appointment_id'  => 'nullable|integer|exists:appointments,id',
        ]);

        // If an appointment is tagged, it must actually belong to this
        // patient — otherwise a crafted request could link a document to
        // (and make it appear under) someone else's visit.
        if ($request->filled('appointment_id')) {
            $owns = Appointment::where('id', $request->appointment_id)
                ->where('patient_id', $patient->id)->exists();
            if (! $owns) {
                return back()->withErrors(['appointment_id' => 'Invalid appointment.']);
            }
        }

        $file = $request->file('file');
        // Stored on the default ('local') disk, which is private — health
        // documents are never served from a public URL. Every read goes
        // through downloadDocument() below, which re-checks ownership.
        $path = $file->store("patient-documents/{$patient->id}");

        PatientDocument::create([
            'patient_id'     => $patient->id,
            'appointment_id' => $request->appointment_id,
            'file_name'      => $file->getClientOriginalName(),
            'file_path'      => $path,
            'file_type'      => $file->getClientOriginalExtension(),
            'doc_type'       => $request->doc_type,
            'notes'          => $request->notes,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function downloadDocument(int $id)
    {
        $patient = $this->patient();
        $document = PatientDocument::where('id', $id)->where('patient_id', $patient->id)->firstOrFail();

        if (! Storage::exists($document->file_path)) {
            abort(404);
        }

        return Storage::download($document->file_path, $document->file_name);
    }

    public function destroyDocument(int $id)
    {
        $patient = $this->patient();
        $document = PatientDocument::where('id', $id)->where('patient_id', $patient->id)->firstOrFail();

        Storage::delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    // Medical History
    public function history(): Response
    {
        $patient = $this->patient();
        $records = Appointment::with('doctor.category', 'prescription')
            ->where('patient_id', $patient->id)->where('status', 'completed')
            ->orderByDesc('appointment_date')->paginate(20)
            ->through(fn ($a) => $this->appointmentCard($a));

        return Inertia::render('patient/History', ['records' => $records]);
    }

}
