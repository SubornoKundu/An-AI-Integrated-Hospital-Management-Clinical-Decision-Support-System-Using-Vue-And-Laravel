<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BedAdmission;
use App\Models\Doctor;
use App\Models\HospitalBed;
use App\Models\HospitalWard;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * BedManagementController
 *
 * Handles all hospital bed & ward management for the Admin panel.
 *
 * Features:
 *  - Ward CRUD (create / edit / delete wards)
 *  - Bed CRUD within each ward
 *  - Patient admission & discharge
 *  - Bed occupancy dashboard overview
 */
class BedManagementController extends Controller
{
    // ── OVERVIEW ─────────────────────────────────────────────────

    /**
     * Bed Management main page — shows ward overview with occupancy stats.
     */
    public function index(): Response
    {
        $wards = HospitalWard::active()
            ->withCount([
                'beds',
                'beds as available_count' => fn($q) => $q->where('status', 'available'),
                'beds as occupied_count'  => fn($q) => $q->where('status', 'occupied'),
            ])
            ->orderBy('name')
            ->get();

        $totalBeds     = HospitalBed::count();
        $occupiedBeds  = HospitalBed::where('status', 'occupied')->count();
        $availableBeds = HospitalBed::where('status', 'available')->count();

        // Active admissions
        $activeAdmissions = BedAdmission::with('patient', 'bed.ward', 'doctor')
            ->where('status', 'admitted')
            ->orderByDesc('admission_date')
            ->get();

        // Revenue from bed charges (discharged)
        $bedRevenue = BedAdmission::where('status', 'discharged')->sum('total_charge');

        return Inertia::render('admin/beds/Index', [
            'wards' => $wards->map(fn ($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'type' => $w->type,
                'floor' => $w->floor,
                'building' => $w->building,
                'beds_count' => $w->beds_count,
                'available_count' => $w->available_count,
                'occupied_count' => $w->occupied_count,
            ]),
            'totalBeds' => $totalBeds,
            'occupiedBeds' => $occupiedBeds,
            'availableBeds' => $availableBeds,
            'activeAdmissions' => $activeAdmissions->map(fn ($a) => [
                'id' => $a->id,
                'patient_name' => $a->patient?->full_name ?? '—',
                'patient_blood_group' => $a->patient?->blood_group,
                'bed_number' => $a->bed?->bed_number ?? '—',
                'ward_name' => $a->bed?->ward?->name ?? '—',
                'doctor_name' => $a->doctor?->name ?? '—',
                'admission_date' => $a->admission_date?->format('d M Y'),
                'admission_date_raw' => $a->admission_date?->format('Y-m-d'),
                'days_admitted' => $a->days_admitted,
                'daily_charge' => (float) ($a->bed?->daily_charge ?? 0),
            ]),
            'bedRevenue' => (float) $bedRevenue,
            // Data needed for the "Add Bed" / "Admit Patient" modals — moved
            // here from inline Blade queries so the Vue page can render them.
            'availableBedsList' => HospitalBed::where('status', 'available')->with('ward')
                ->orderBy('bed_number')->get()->map(fn ($b) => [
                    'id' => $b->id,
                    'label' => $b->bed_number . ' — ' . ($b->ward?->name ?? ''),
                ]),
            'patientsList' => Patient::orderBy('first_name')->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->full_name,
            ]),
            'doctorsList' => Doctor::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── WARD MANAGEMENT ──────────────────────────────────────────

    public function storeWard(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'type'     => 'required|in:general,icu,ccu,cabin,emergency,pediatric,maternity',
            'floor'    => 'nullable|string|max:20',
            'building' => 'nullable|string|max:50',
        ]);

        HospitalWard::create($data);
        return back()->with('success', 'Ward created successfully.');
    }

    public function destroyWard($id)
    {
        $ward = HospitalWard::findOrFail($id);

        // Prevent deletion if occupied beds exist
        if ($ward->beds()->where('status', 'occupied')->exists()) {
            return back()->with('error', 'Cannot delete ward — it has occupied beds.');
        }

        $ward->delete();
        return back()->with('success', 'Ward deleted.');
    }

    // ── BED MANAGEMENT ───────────────────────────────────────────

    public function storeBed(Request $request)
    {
        $data = $request->validate([
            'ward_id'      => 'required|exists:hospital_wards,id',
            'bed_number'   => 'required|string|max:20',
            'bed_type'     => 'required|in:standard,deluxe,icu,cabin',
            'daily_charge' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:255',
        ]);

        // Ensure bed_number is unique in ward
        $exists = HospitalBed::where('ward_id', $data['ward_id'])
            ->where('bed_number', $data['bed_number'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Bed number already exists in this ward.');
        }

        HospitalBed::create($data);

        // Update total_beds count on ward
        $this->syncWardBedCount($data['ward_id']);

        return back()->with('success', 'Bed added successfully.');
    }

    public function updateBedStatus(Request $request, $id)
    {
        $bed = HospitalBed::findOrFail($id);
        $request->validate(['status' => 'required|in:available,maintenance,reserved']);

        // Cannot manually change to occupied
        if ($bed->status === 'occupied') {
            return back()->with('error', 'Cannot change status of occupied bed. Discharge the patient first.');
        }

        $bed->update(['status' => $request->status]);
        return back()->with('success', 'Bed status updated.');
    }

    public function destroyBed($id)
    {
        $bed = HospitalBed::findOrFail($id);

        if ($bed->status === 'occupied') {
            return back()->with('error', 'Cannot delete occupied bed.');
        }

        $wardId = $bed->ward_id;
        $bed->delete();
        $this->syncWardBedCount($wardId);

        return back()->with('success', 'Bed deleted.');
    }

    // ── ADMISSIONS ───────────────────────────────────────────────

    /**
     * Admit a patient to a specific bed.
     */
    public function admitPatient(Request $request)
    {
        $data = $request->validate([
            'bed_id'         => 'required|exists:hospital_beds,id',
            'patient_id'     => 'required|exists:patients,id',
            'doctor_id'      => 'required|exists:doctors,id',
            'admission_date' => 'required|date',
            'diagnosis'      => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        // Same pattern as the appointment-booking fix: without the lock, two
        // concurrent admission requests for the same bed could both pass the
        // availability check before either one's create() runs — a physical
        // bed assigned to two patients at once, not just a scheduling glitch.
        $error = DB::transaction(function () use ($data) {
            $bed = HospitalBed::where('id', $data['bed_id'])->lockForUpdate()->firstOrFail();

            if (! $bed->isAvailable()) {
                return 'This bed is not available for admission.';
            }

            BedAdmission::create(array_merge($data, ['status' => 'admitted']));
            $bed->update(['status' => 'occupied']);

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Patient admitted successfully.');
    }

    /**
     * Discharge a patient and calculate charges.
     */
    public function dischargePatient(Request $request, $admissionId)
    {
        $admission = BedAdmission::with('bed')->findOrFail($admissionId);

        if ($admission->status === 'discharged') {
            return back()->with('error', 'Patient is already discharged.');
        }

        $request->validate([
            'discharge_date' => 'required|date|after_or_equal:' . $admission->admission_date,
        ]);

        $dischargeDate = $request->discharge_date;
        $days          = max(1, \Carbon\Carbon::parse($admission->admission_date)
                              ->diffInDays($dischargeDate));
        $totalCharge   = $days * ($admission->bed->daily_charge ?? 0);

        $admission->update([
            'discharge_date' => $dischargeDate,
            'status'         => 'discharged',
            'total_charge'   => $totalCharge,
        ]);

        // Free the bed
        $admission->bed->update(['status' => 'available']);

        return back()->with('success', "Patient discharged. Total bed charge: ৳" . number_format($totalCharge, 2));
    }

    // ── PRIVATE HELPERS ──────────────────────────────────────────

    private function syncWardBedCount(int $wardId): void
    {
        $count = HospitalBed::where('ward_id', $wardId)->count();
        HospitalWard::where('id', $wardId)->update(['total_beds' => $count]);
    }
}
