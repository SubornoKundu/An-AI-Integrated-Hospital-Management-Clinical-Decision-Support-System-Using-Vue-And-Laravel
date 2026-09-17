<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorBlockedDate;
use App\Models\DoctorCategory;
use App\Models\DoctorRating;
use App\Models\Notification;
use App\Models\Prescription;
use App\Models\PrescriptionTemplate;
use App\Models\Referral;
use App\Models\Vital;
use App\Services\NotificationService;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected NotificationService $ns;

    protected ProfilePhotoService $photos;

    public function __construct(NotificationService $ns, ProfilePhotoService $photos)
    {
        $this->ns = $ns;
        $this->photos = $photos;
    }

    private function doctor()
    {
        return Auth::guard('doctor')->user();
    }

    // ── Dashboard ────────────────────────────────────────────────
    public function dashboard(): Response
    {
        $doctor = $this->doctor();
        $today  = now()->toDateString();

        $todayAppointments = Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->where('appointment_date', $today)
            ->where('status', 'active')
            ->orderBy('slot_start')
            ->get();

        $stats = [
            'today'     => $todayAppointments->count(),
            'upcoming'  => Appointment::where('doctor_id', $doctor->id)->where('status','active')->where('appointment_date','>=',$today)->count(),
            'completed' => Appointment::where('doctor_id', $doctor->id)->where('status','completed')->count(),
            // Sum each appointment's own captured fee (falling back to the
            // doctor's current fee only for older rows from before that
            // column existed) rather than completed-count × current fee —
            // multiplying by the current fee silently misstates lifetime
            // earnings the moment a doctor's rate ever changes.
            'earnings'  => (float) Appointment::where('doctor_id', $doctor->id)->where('status', 'completed')
                ->get(['consultancy_fee'])
                ->sum(fn ($a) => $a->consultancy_fee ?? $doctor->consultancy_fee),
        ];

        return Inertia::render('doctor/Dashboard', [
            'doctor' => [
                'name' => $doctor->name,
                'category' => $doctor->category?->name ?? 'Specialist',
                'consultancy_fee' => (float) $doctor->consultancy_fee,
            ],
            'todayAppointments' => $todayAppointments->map(fn (Appointment $a) => [
                'id' => $a->id,
                'slot_start' => $a->slot_start,
                'slot_end' => $a->slot_end,
                'status' => $a->status,
                'patient' => $a->patient ? [
                    'full_name' => $a->patient->full_name,
                    'contact' => $a->patient->contact,
                    'gender' => $a->patient->gender,
                    'age' => $a->patient->age,
                    'blood_group' => $a->patient->blood_group,
                    'photo' => $a->patient->photo ? asset($a->patient->photo) : null,
                ] : null,
            ]),
            'stats' => $stats,
            'pendingFollowUps' => $this->pendingFollowUps($doctor->id),
        ]);
    }

    // Patients this doctor asked to follow up who haven't come back yet —
    // mirrors the same logic on the patient dashboard, scoped the other
    // direction. Same reasoning: no scheduling infrastructure exists in
    // this app, so this has to be an in-app list the doctor sees on login,
    // not an automated reminder.
    private function pendingFollowUps(int $doctorId)
    {
        return Prescription::with('appointment', 'patient')
            ->where('doctor_id', $doctorId)
            ->whereNotNull('follow_up_date')
            ->get()
            ->filter(fn ($rx) => $rx->appointment && ! Appointment::where('doctor_id', $doctorId)
                ->where('patient_id', $rx->patient_id)
                ->where('appointment_date', '>', $rx->appointment->appointment_date)
                ->whereIn('status', ['active', 'completed'])
                ->exists())
            ->map(fn ($rx) => [
                'prescription_id' => $rx->id,
                'patient_name' => $rx->patient?->full_name ?? 'Unknown',
                'diagnosis' => $rx->diagnosis,
                'follow_up_date' => $rx->follow_up_date->format('d M Y'),
                'is_overdue' => $rx->follow_up_date->isPast(),
            ])
            ->sortBy('follow_up_date')->values();
    }

    // ── Appointments ─────────────────────────────────────────────
    public function appointments(Request $request): Response
    {
        $doctor = $this->doctor();
        $query  = Appointment::with('patient','prescription')
            ->where('doctor_id', $doctor->id);

        if ($request->filled('date')) $query->where('appointment_date', $request->date);
        if ($request->filled('status')) $query->where('status', $request->status);

        $appointments = $query->orderByDesc('appointment_date')->orderBy('slot_start')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Appointment $a) => [
                'id' => $a->id,
                'appointment_date' => $a->appointment_date->format('d M Y'),
                'slot_start' => $a->slot_start,
                'slot_end' => $a->slot_end,
                'status' => $a->status,
                'has_prescription' => (bool) $a->prescription,
                'patient' => $a->patient ? [
                    'full_name' => $a->patient->full_name,
                    'gender' => $a->patient->gender,
                    'age' => $a->patient->age,
                    'blood_group' => $a->patient->blood_group,
                    'photo' => $a->patient->photo ? asset($a->patient->photo) : null,
                ] : null,
            ]);

        return Inertia::render('doctor/Appointments', [
            'appointments' => $appointments,
            'filters' => [
                'date' => $request->get('date'),
                'status' => $request->get('status'),
            ],
        ]);
    }

    // ── Mark appointment complete ────────────────────────────────
    public function completeAppointment(Request $request, int $id)
    {
        $doctor      = $this->doctor();
        $appointment = Appointment::where('id',$id)->where('doctor_id',$doctor->id)->where('status','active')->firstOrFail();
        $appointment->update(['status' => 'completed', 'visit_notes' => $request->visit_notes]);

        $this->ns->send($appointment->patient_id, 'patient',
            'Appointment Completed',
            "Your appointment with Dr. {$doctor->name} has been marked as completed.",
            'general'
        );

        return back()->with('success', 'Appointment marked as completed.');
    }

    // ── Cancel appointment ───────────────────────────────────────
    public function cancelAppointment(int $id)
    {
        $doctor      = $this->doctor();
        $appointment = Appointment::where('id',$id)->where('doctor_id',$doctor->id)->where('status','active')->firstOrFail();
        $appointment->update(['status' => 'cancelled_by_doctor']);

        $this->ns->send($appointment->patient_id, 'patient',
            'Appointment Cancelled',
            "Dr. {$doctor->name} has cancelled your appointment on {$appointment->appointment_date->format('d M Y')}.",
            'cancellation'
        );

        return back()->with('success', 'Appointment cancelled.');
    }

    // ── Write Prescription ───────────────────────────────────────
    public function writePrescription(int $appointmentId): Response
    {
        $doctor      = $this->doctor();
        $appointment = Appointment::with('patient')
            ->where('id', $appointmentId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $prescription = $appointment->prescription;
        $templates    = PrescriptionTemplate::where('doctor_id', $doctor->id)->get();

        $pastPrescriptions = Appointment::with('prescription', 'doctor.category')
            ->where('patient_id', $appointment->patient_id)
            ->where('status', 'completed')
            ->where('id', '!=', $appointmentId)
            ->whereHas('prescription')
            ->orderByDesc('appointment_date')
            ->take(3)
            ->get();

        return Inertia::render('doctor/Prescription', [
            'appointment' => [
                'id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date->format('d M Y'),
                'patient' => $appointment->patient ? [
                    'id' => $appointment->patient->id,
                    'full_name' => $appointment->patient->full_name,
                    'gender' => $appointment->patient->gender,
                    'age' => $appointment->patient->age,
                    'blood_group' => $appointment->patient->blood_group,
                    'contact' => $appointment->patient->contact,
                    'photo' => $appointment->patient->photo ? asset($appointment->patient->photo) : null,
                ] : null,
            ],
            'prescription' => $prescription ? [
                'diagnosis' => $prescription->diagnosis,
                'medicines' => $prescription->medicines,
                'instructions' => $prescription->instructions,
                'follow_up_date' => $prescription->follow_up_date?->format('Y-m-d'),
            ] : null,
            'vital' => $appointment->vital ? [
                'bp_systolic' => $appointment->vital->bp_systolic,
                'bp_diastolic' => $appointment->vital->bp_diastolic,
                'pulse_rate' => $appointment->vital->pulse_rate,
                'temperature_celsius' => $appointment->vital->temperature_celsius,
                'spo2' => $appointment->vital->spo2,
            ] : null,
            'templates' => $templates->map(fn (PrescriptionTemplate $t) => [
                'id' => $t->id,
                'template_name' => $t->template_name,
                'diagnosis' => $t->diagnosis,
                'medicines' => $t->medicines,
                'instructions' => $t->instructions,
            ]),
            'pastPrescriptions' => $pastPrescriptions->map(fn (Appointment $p) => [
                'id' => $p->id,
                'appointment_date' => $p->appointment_date->format('d M Y'),
                'category' => $p->doctor?->category?->name ?? 'General',
                'diagnosis' => $p->prescription->diagnosis,
                'medicines' => $p->prescription->medicines,
            ]),
            'categories' => DoctorCategory::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function savePrescription(Request $request, int $appointmentId)
    {
        $request->validate([
            'diagnosis'    => 'required|string',
            'medicines'    => 'required|string',
            'instructions' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'safety_check_override' => 'nullable|string',
            // Vitals are optional — a doctor may not measure all four every
            // visit. Ranges are generous clinical bounds (catch typos, not
            // borderline-but-real values).
            'bp_systolic'          => 'nullable|integer|min:40|max:300',
            'bp_diastolic'         => 'nullable|integer|min:20|max:200',
            'pulse_rate'           => 'nullable|integer|min:20|max:250',
            'temperature_celsius'  => 'nullable|numeric|min:25|max:45',
            'spo2'                 => 'nullable|integer|min:0|max:100',
        ]);

        $doctor      = $this->doctor();
        $appointment = Appointment::where('id', $appointmentId)->where('doctor_id', $doctor->id)->firstOrFail();

        Prescription::updateOrCreate(
            ['appointment_id' => $appointmentId],
            [
                'patient_id'     => $appointment->patient_id,
                'doctor_id'      => $doctor->id,
                'diagnosis'      => $request->diagnosis,
                'medicines'      => $request->medicines,
                'instructions'   => $request->instructions,
                'follow_up_date' => $request->follow_up_date,
                'safety_check_override' => $request->safety_check_override,
            ]
        );

        $vitals = $request->only(['bp_systolic', 'bp_diastolic', 'pulse_rate', 'temperature_celsius', 'spo2']);
        if (array_filter($vitals, fn ($v) => $v !== null && $v !== '')) {
            Vital::updateOrCreate(
                ['appointment_id' => $appointmentId],
                ['patient_id' => $appointment->patient_id, ...$vitals]
            );
        }

        $appointment->update(['status' => 'completed']);

        $this->ns->send($appointment->patient_id, 'patient',
            'Prescription Ready',
            "Dr. {$doctor->name} has written your prescription.",
            'prescription',
            route('patient.appointments')
        );

        return redirect()->route('doctor.appointments')->with('success', 'Prescription saved.');
    }

    // ── Templates ────────────────────────────────────────────────
    public function templates(): Response
    {
        $doctor    = $this->doctor();
        $templates = PrescriptionTemplate::where('doctor_id', $doctor->id)->orderByDesc('created_at')->get();

        return Inertia::render('doctor/Templates', [
            'templates' => $templates->map(fn (PrescriptionTemplate $t) => [
                'id' => $t->id,
                'template_name' => $t->template_name,
                'diagnosis' => $t->diagnosis,
                'medicines' => $t->medicines,
                'instructions' => $t->instructions,
            ]),
        ]);
    }

    public function saveTemplate(Request $request)
    {
        $request->validate([
            'template_name' => 'required|string|max:100',
            'diagnosis'     => 'required|string',
            'medicines'     => 'required|string',
            'instructions'  => 'nullable|string',
        ]);

        $doctor = $this->doctor();

        if ($request->filled('id')) {
            PrescriptionTemplate::where('id', $request->id)->where('doctor_id', $doctor->id)->update($request->only(['template_name','diagnosis','medicines','instructions']));
        } else {
            PrescriptionTemplate::create(array_merge($request->only(['template_name','diagnosis','medicines','instructions']), ['doctor_id' => $doctor->id]));
        }

        return back()->with('success', 'Template saved.');
    }

    public function deleteTemplate(int $id)
    {
        PrescriptionTemplate::where('id', $id)->where('doctor_id', $this->doctor()->id)->delete();
        return back()->with('success', 'Template deleted.');
    }

    // ── Referrals ────────────────────────────────────────────────
    public function referrals(): Response
    {
        $doctor    = $this->doctor();
        $referrals = Referral::with('patient','toCategory','appointment')
            ->where('from_doctor_id', $doctor->id)
            ->orderByDesc('created_at')
            ->paginate(10)
            ->through(fn (Referral $r) => [
                'id' => $r->id,
                'created_at' => $r->created_at->format('d M Y'),
                'reason' => $r->reason,
                'patient_name' => $r->patient?->full_name ?? '—',
                'to_category' => $r->toCategory?->name,
            ]);
        $categories = DoctorCategory::active()->orderBy('name')->get();

        // Patients this doctor has actually seen
        $patients = \App\Models\Patient::whereHas('appointments', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })->orderBy('first_name')->orderBy('last_name')->get();

        // Their appointments with this doctor grouped by patient_id
        $patientAppointments = \App\Models\Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', ['completed','active'])
            ->orderByDesc('appointment_date')
            ->get()
            ->groupBy('patient_id')
            ->map(fn($group) => $group->map(fn($a) => [
                'id'    => $a->id,
                'label' => $a->appointment_date->format('d M Y').' — '.$a->slot_start.' ('.$a->status.')',
            ])->values());

        return Inertia::render('doctor/Referrals', [
            'referrals' => $referrals,
            'categories' => $categories->map(fn (DoctorCategory $c) => ['id' => $c->id, 'name' => $c->name]),
            'patients' => $patients->map(fn ($p) => ['id' => $p->id, 'name' => $p->full_name]),
            'patientAppointments' => $patientAppointments,
        ]);
    }

    public function createReferral(Request $request)
    {
        $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'to_category_id' => 'nullable|exists:doctor_categories,id',
            'reason'         => 'required|string',
            'notes'          => 'nullable|string',
        ]);

        $doctor = $this->doctor();

        $referral = Referral::create([
            'from_doctor_id' => $doctor->id,
            'to_category_id' => $request->to_category_id,
            'patient_id'     => $request->patient_id,
            'appointment_id' => $request->appointment_id,
            'reason'         => $request->reason,
            'notes'          => $request->notes,
        ]);

        $this->ns->send($request->patient_id, 'patient',
            'Referral Created',
            "Dr. {$doctor->name} has referred you for further consultation.",
            'general'
        );

        // The referral used to just sit in the database with no way for
        // anyone in the target specialty to ever find out it existed —
        // the only "sent" list was the referring doctor's own outgoing
        // history. A referral nobody on the receiving end knows about
        // isn't really a referral. Notify every active doctor in that
        // specialty directly.
        if ($referral->to_category_id) {
            $patientName = $referral->patient?->full_name ?? 'A patient';
            Doctor::where('category_id', $referral->to_category_id)
                ->where('is_active', true)
                ->get()
                ->each(fn (Doctor $recipient) => $this->ns->send($recipient->id, 'doctor',
                    'New Patient Referral',
                    "{$patientName} was referred to your specialty by Dr. {$doctor->name}. Reason: {$referral->reason}",
                    'general'
                ));
        }

        // A referral tied to a specific appointment means that visit's
        // clinical work concluded with a referral rather than a
        // prescription — close it out the same way saving a prescription
        // would, so it stops sitting open and still counts toward visit
        // history, total_visits, etc. elsewhere in the app.
        if ($request->appointment_id) {
            Appointment::where('id', $request->appointment_id)
                ->where('doctor_id', $doctor->id)
                ->update(['status' => 'completed']);
        }

        return back()->with('success', 'Referral created.');
    }

    // ── Earnings ─────────────────────────────────────────────────
    public function earnings(Request $request): Response
    {
        $doctor = $this->doctor();
        $month  = $request->get('month', now()->format('Y-m'));

        // DATE_FORMAT() is MySQL-only and doesn't exist on SQLite (the
        // test suite's driver, per phpunit.xml). whereYear()/whereMonth()
        // are Laravel's portable equivalent — the query builder compiles
        // them correctly for whichever driver is actually connected.
        $monthDate = \Carbon\Carbon::parse($month.'-01');

        $completed = Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereYear('appointment_date', $monthDate->year)
            ->whereMonth('appointment_date', $monthDate->month)
            ->orderBy('appointment_date')
            ->get();

        // Sum each appointment's own captured fee (falling back to the
        // doctor's current fee for older rows from before that column
        // existed) rather than count × current fee — the latter silently
        // misstates earnings retroactively the moment a doctor's rate
        // changes, for the selected month, its daily breakdown, and the
        // previous-month comparison below.
        $feeOf = fn (Appointment $a) => $a->consultancy_fee ?? $doctor->consultancy_fee;

        $total = (float) $completed->sum($feeOf);

        // Daily chart data
        $dailyData = $completed
            ->groupBy(fn($a) => $a->appointment_date->format('d'))
            ->map(fn($g) => (float) $g->sum($feeOf))
            ->toArray();

        // Days in month for chart labels
        $daysInMonth = \Carbon\Carbon::parse($month.'-01')->daysInMonth;
        $chartLabels = array_map(fn($d) => str_pad($d, 2, '0', STR_PAD_LEFT), range(1, $daysInMonth));
        $chartValues = array_map(fn($d) => $dailyData[str_pad($d,2,'0',STR_PAD_LEFT)] ?? 0, range(1, $daysInMonth));

        // Previous month comparison
        $prevMonthDate = \Carbon\Carbon::parse($month.'-01')->subMonth();
        $prevTotal     = (float) Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereYear('appointment_date', $prevMonthDate->year)
            ->whereMonth('appointment_date', $prevMonthDate->month)
            ->get(['consultancy_fee'])
            ->sum($feeOf);

        return Inertia::render('doctor/Earnings', [
            'completed' => $completed->map(fn (Appointment $a) => [
                'id' => $a->id,
                'appointment_date' => $a->appointment_date->format('d M Y'),
                'patient_name' => $a->patient?->full_name ?? '—',
            ]),
            'total' => $total,
            'month' => $month,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'prevTotal' => $prevTotal,
        ]);
    }

    // ── Calendar ─────────────────────────────────────────────────
    // ── Calendar ─────────────────────────────────────────────────
    public function calendar(): Response
    {
        $doctor = $this->doctor();
        $appointments = Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->where('status', 'active')
            ->where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('slot_start')
            ->get()
            ->map(fn($a) => [
                'title' => $a->patient->full_name . ' @ ' . $a->slot_start,
                'start' => $a->appointment_date->format('Y-m-d') . 'T' . $a->slot_start,
                'end'   => $a->appointment_date->format('Y-m-d') . 'T' . $a->slot_end,
                'color' => '#00d4aa',
            ]);

        $blockedDates = DoctorBlockedDate::where('doctor_id', $doctor->id)
            ->orderBy('blocked_date')->get();

        return Inertia::render('doctor/Calendar', [
            'appointments' => $appointments,
            'blockedDates' => $blockedDates->map(fn (DoctorBlockedDate $b) => [
                'id' => $b->id,
                'blocked_date' => $b->blocked_date->format('d M Y'),
                'reason' => $b->reason,
            ]),
        ]);
    }

    public function storeBlockedDate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'blocked_date' => 'required|date|after_or_equal:today',
            'reason'       => 'nullable|string|max:200',
        ]);
        $doctor = $this->doctor();
        DoctorBlockedDate::firstOrCreate(
            ['doctor_id' => $doctor->id, 'blocked_date' => $request->blocked_date],
            ['reason'    => $request->reason]
        );

        // Blocking stops NEW bookings on this date, but says nothing about
        // ones that already exist — a patient could otherwise show up to a
        // day the doctor has now marked unavailable. Cancel those and
        // notify the patients directly, same principle as the holiday fix.
        $affected = Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->where('appointment_date', $request->blocked_date)
            ->where('status', 'active')
            ->get();

        foreach ($affected as $appt) {
            $appt->update(['status' => 'cancelled_by_doctor']);
            if ($appt->patient) {
                $this->ns->send($appt->patient_id, 'patient',
                    'Appointment Cancelled',
                    "Dr. {$doctor->name} is unavailable on {$appt->appointment_date->format('d M Y')} and has cancelled your appointment. Please rebook for another date.",
                    'cancellation'
                );
            }
        }

        $msg = 'Date blocked. Patients cannot book on this day.';
        if ($affected->count() > 0) {
            $msg .= " {$affected->count()} existing appointment(s) on this date were cancelled and the affected patients notified.";
        }

        return back()->with('success', $msg);
    }

    public function deleteBlockedDate(int $id)
    {
        DoctorBlockedDate::where('id', $id)->where('doctor_id', $this->doctor()->id)->delete();
        return back()->with('success', 'Date unblocked successfully.');
    }

    // ── Notifications ────────────────────────────────────────────
    public function notifications(): Response
    {
        $doctor = $this->doctor();
        $items  = Notification::where('user_id', $doctor->id)->where('user_role', 'doctor')
            ->orderByDesc('created_at')->paginate(20)
            ->through(fn (Notification $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'is_read' => (bool) $n->is_read,
                'link' => $n->link,
                'created_at' => $n->created_at->diffForHumans(),
            ]);
        Notification::where('user_id', $doctor->id)->where('user_role', 'doctor')->update(['is_read' => true]);

        return Inertia::render('doctor/Notifications', ['items' => $items]);
    }

    // ── Messages inbox ───────────────────────────────────────────
    public function messages(): Response
    {
        $doctor = $this->doctor();

        $conversations = Appointment::with(['patient', 'chatMessages'])
            ->where('doctor_id', $doctor->id)
            ->whereHas('chatMessages')
            ->get()
            ->sortByDesc(fn (Appointment $a) => $a->chatMessages->max('sent_at'))
            ->values()
            ->map(function (Appointment $a) {
                $last = $a->chatMessages->sortByDesc('sent_at')->first();
                $unread = $a->chatMessages->where('sender_role', 'patient')->where('is_read', false)->count();

                return [
                    'appointment_id' => $a->id,
                    'patient_name' => $a->patient?->full_name ?? '—',
                    'patient_photo' => $a->patient?->photo ? asset($a->patient->photo) : null,
                    'appointment_date' => $a->appointment_date->format('d M Y'),
                    'last_message' => $last->message,
                    'last_message_time' => $last->sent_at->diffForHumans(),
                    'last_sender' => $last->sender_role,
                    'unread_count' => $unread,
                ];
            });

        return Inertia::render('doctor/Messages', ['conversations' => $conversations]);
    }

    public function chat(int $appointmentId): Response
    {
        $doctor      = $this->doctor();
        $appointment = Appointment::with('patient')
            ->where('id', $appointmentId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $messages = $appointment->chatMessages()->orderBy('sent_at')->get();
        $appointment->chatMessages()->where('sender_role','patient')->update(['is_read' => true]);

        return Inertia::render('doctor/Chat', [
            'appointment' => [
                'id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date->format('d M Y'),
                'slot_start' => $appointment->slot_start,
                'slot_end' => $appointment->slot_end,
                'patient' => $appointment->patient ? [
                    'full_name' => $appointment->patient->full_name,
                    'blood_group' => $appointment->patient->blood_group,
                    'age' => $appointment->patient->age,
                    'photo' => $appointment->patient->photo ? asset($appointment->patient->photo) : null,
                    'is_online' => $appointment->patient->last_seen_at
                        && $appointment->patient->last_seen_at->gt(now()->subMinutes(2)),
                ] : null,
            ],
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_role' => $m->sender_role,
                'message' => $m->message,
                'time' => \Carbon\Carbon::parse($m->sent_at)->format('h:i A'),
            ]),
        ]);
    }

    public function sendChat(Request $request, int $appointmentId)
    {
        $request->validate(['message' => 'required|string|max:1000']);
        $doctor      = $this->doctor();
        $appointment = Appointment::where('id', $appointmentId)->where('doctor_id', $doctor->id)->firstOrFail();

        $appointment->chatMessages()->create([
            'sender_role' => 'doctor',
            'sender_id'   => $doctor->id,
            'message'     => $request->message,
        ]);

        $this->ns->send($appointment->patient_id, 'patient',
            'New Chat Message',
            "Dr. {$doctor->name} sent you a message.",
            'chat',
            route('patient.appointments')
        );

        return back();
    }

    // ── Profile ──────────────────────────────────────────────────
    public function profile(): Response
    {
        $doctor = $this->doctor();
        $totalPatients = Appointment::where('doctor_id', $doctor->id)->where('status','completed')->count();
        $avgRating = round(DoctorRating::where('doctor_id', $doctor->id)->avg('rating') ?? 0, 1);

        return Inertia::render('doctor/Profile', [
            'doctor' => [
                'name' => $doctor->name,
                'email' => $doctor->email,
                'phone' => $doctor->phone,
                'bio' => $doctor->bio,
                'category' => $doctor->category?->name ?? 'Specialist',
                'consultancy_fee' => (float) ($doctor->consultancy_fee ?? 0),
                'joined' => $doctor->created_at->format('d M Y'),
                'total_patients' => $totalPatients,
                'avg_rating' => $avgRating,
                'photo' => $doctor->photo ? asset($doctor->photo) : null,
                'has_custom_photo' => $doctor->photo && ! $this->photos->isPlaceholder($doctor->photo, $doctor->id),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $doctor = $this->doctor();
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'bio'   => 'nullable|string|max:400',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = $request->only(['name','phone','bio']);
        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $data['password'] = Hash::make($request->password);
        }
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $data['photo'] = $this->photos->store($request->file('photo'), 'doctors', 'doctor', $doctor->id, $doctor->photo);
        }

        $doctor->update($data);
        return back()->with('success', 'Profile updated.');
    }

    /** Remove the doctor's custom photo and reset it to a generated placeholder avatar. */
    public function deletePhoto()
    {
        $doctor = $this->doctor();

        if (! $doctor->photo || $this->photos->isPlaceholder($doctor->photo, $doctor->id)) {
            return back()->with('error', 'You do not have a custom photo to remove.');
        }

        $doctor->update([
            'photo' => $this->photos->resetToPlaceholder($doctor->name, 'doctors', $doctor->id, $doctor->gender, $doctor->photo),
        ]);

        return back()->with('success', 'Profile photo removed.');
    }
}
