<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;

function bookingPayload(Doctor $doctor, string $date, string $slot): array
{
    return [
        'doctor_id' => $doctor->id,
        'appointment_date' => $date,
        'slot_start' => $slot,
    ];
}

// SlotService (app/Services/SlotService.php) only returns bookable slots for
// a day that has an active DoctorSchedule row — without this, every booking
// attempt below would fail with "slot not available" regardless of any real
// conflict, since there'd be no slots at all. Covers all 7 days so these
// tests aren't fragile to which day-of-week "3 days from now" happens to be.
function giveDoctorAFullWeekSchedule(Doctor $doctor): void
{
    for ($day = 0; $day <= 6; $day++) {
        DoctorSchedule::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => $day,
            'start_time' => '08:00',
            'end_time' => '18:00',
            'is_active' => true,
        ]);
    }
}

it('prevents two different patients from booking the same doctor/date/slot', function () {
    $doctor = Doctor::factory()->create();
    giveDoctorAFullWeekSchedule($doctor);
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();
    $date = now()->addDays(3)->toDateString();

    $firstBooking = $this->actingAs($patientA, 'patient')
        ->post('/patient/book/confirm', bookingPayload($doctor, $date, '10:00'));

    // Diagnostic checkpoint: does patient A's booking exist BEFORE patient B
    // even attempts anything? This isolates whether the bug is in the first
    // request succeeding at all, or specifically in what happens after a
    // second, conflicting request.
    $afterFirst = Appointment::where('doctor_id', $doctor->id)
        ->where('appointment_date', $date)
        ->where('slot_start', '10:00')
        ->count();
    $firstBookingSession = $firstBooking->getSession()->all();
    expect($afterFirst)->toBe(1, "After request 1 alone: found {$afterFirst} appointment(s). "
        ."First booking response status: {$firstBooking->getStatusCode()}. "
        ."Session after request 1: ".json_encode($firstBookingSession));

    $secondBooking = $this->actingAs($patientB, 'patient')
        ->post('/patient/book/confirm', bookingPayload($doctor, $date, '10:00'));

    $afterSecond = Appointment::where('doctor_id', $doctor->id)
        ->where('appointment_date', $date)
        ->where('slot_start', '10:00')
        ->where('status', 'active')
        ->count();
    $secondBookingSession = $secondBooking->getSession()->all();

    expect($afterSecond)->toBe(1, "After both requests: found {$afterSecond} active appointment(s), expected 1. "
        ."Second booking response status: {$secondBooking->getStatusCode()}. "
        ."Session after request 2: ".json_encode($secondBookingSession)
        ." All appointments for this doctor/date/slot (any status): "
        .json_encode(Appointment::where('doctor_id', $doctor->id)
            ->where('appointment_date', $date)
            ->where('slot_start', '10:00')
            ->get(['id', 'patient_id', 'status'])->toArray()));

    $secondBooking->assertSessionHas('error');
});

it('allows the same patient to book two different slots with the same doctor', function () {
    $doctor = Doctor::factory()->create();
    giveDoctorAFullWeekSchedule($doctor);
    $patient = Patient::factory()->create();
    $date = now()->addDays(3)->toDateString();

    $this->actingAs($patient, 'patient')->post('/patient/book/confirm', bookingPayload($doctor, $date, '10:00'));
    $this->actingAs($patient, 'patient')->post('/patient/book/confirm', bookingPayload($doctor, $date, '10:15'));

    expect(Appointment::where('patient_id', $patient->id)->where('status', 'active')->count())->toBe(2);
});

it('allows two different doctors to be booked for the same date/slot by different patients', function () {
    $doctorA = Doctor::factory()->create();
    giveDoctorAFullWeekSchedule($doctorA);
    $doctorB = Doctor::factory()->create();
    giveDoctorAFullWeekSchedule($doctorB);
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();
    $date = now()->addDays(3)->toDateString();

    $this->actingAs($patientA, 'patient')->post('/patient/book/confirm', bookingPayload($doctorA, $date, '10:00'));
    $this->actingAs($patientB, 'patient')->post('/patient/book/confirm', bookingPayload($doctorB, $date, '10:00'));

    expect(Appointment::where('status', 'active')->count())->toBe(2);
});

it('frees a slot back up after the original booking is cancelled', function () {
    $doctor = Doctor::factory()->create();
    giveDoctorAFullWeekSchedule($doctor);
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();
    $date = now()->addDays(3)->toDateString();

    $appointment = Appointment::create([
        'patient_id' => $patientA->id,
        'doctor_id' => $doctor->id,
        'appointment_date' => $date,
        'slot_start' => '10:00',
        'slot_end' => '10:15',
        'status' => 'active',
        'consultancy_fee' => $doctor->consultancy_fee,
    ]);

    $this->actingAs($patientA, 'patient')->post("/patient/appointments/{$appointment->id}/cancel");

    $afterCancel = $appointment->fresh()->status;

    $rebooked = $this->actingAs($patientB, 'patient')
        ->post('/patient/book/confirm', bookingPayload($doctor, $date, '10:00'));

    $activeCount = Appointment::where('doctor_id', $doctor->id)
        ->where('appointment_date', $date)
        ->where('slot_start', '10:00')
        ->where('status', 'active')
        ->count();

    expect($activeCount)->toBe(1, "Status after cancel: {$afterCancel}. "
        ."Rebooking response status: {$rebooked->getStatusCode()}. "
        ."Session after rebooking attempt: ".json_encode($rebooked->getSession()->all())
        ." All appointments for this doctor/date/slot: "
        .json_encode(Appointment::where('doctor_id', $doctor->id)
            ->where('appointment_date', $date)
            ->where('slot_start', '10:00')
            ->get(['id', 'patient_id', 'status'])->toArray()));
    expect(Appointment::where('patient_id', $patientB->id)->where('status', 'active')->exists())->toBeTrue();
});

it('captures the doctor\'s consultancy fee on the appointment at booking time', function () {
    $doctor = Doctor::factory()->create(['consultancy_fee' => 1234]);
    giveDoctorAFullWeekSchedule($doctor);
    $patient = Patient::factory()->create();
    $date = now()->addDays(3)->toDateString();

    $this->actingAs($patient, 'patient')->post('/patient/book/confirm', bookingPayload($doctor, $date, '10:00'));

    $appointment = Appointment::where('patient_id', $patient->id)->first();
    // Regression check for the Phase 7 fix: consultancy_fee must actually
    // persist, not just be silently dropped by mass-assignment protection.
    expect((float) $appointment->consultancy_fee)->toBe(1234.0);
});
