<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;

/*
|--------------------------------------------------------------------------
| AI Pre-Consultation Briefing — allergy-recall spot check
|--------------------------------------------------------------------------
| This targets the exact risk named for this feature in Table 5.1: "a
| clinically relevant fact (e.g. an allergy) is omitted from the summary."
| The production prompt explicitly instructs that a known allergy must
| always be mentioned in patient_snapshot — this test checks, on the real
| production code path, whether it actually is. This is a recall/
| completeness check, not a general "accuracy" claim.
|
| Run with: ./vendor/bin/pest tests/Feature/AiPreConsultationBriefingSpotCheckTest.php
|--------------------------------------------------------------------------
*/

function makeUpcomingAppointment(array $patientOverrides = []): array
{
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create($patientOverrides);
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_date' => now()->toDateString(),
        'slot_start' => '11:00',
        'slot_end' => '11:15',
        'status' => 'active',
        'consultancy_fee' => $doctor->consultancy_fee,
    ]);

    return [$doctor, $appointment];
}

function runBriefing(Doctor $doctor, Appointment $appointment): array
{
    $response = test()->actingAs($doctor, 'doctor')
        ->post("/doctor/prescription/{$appointment->id}/ai-summary");

    $response->assertOk();

    return $response->json();
}

function briefingText(array $data): string
{
    return strtolower(implode(' | ', [
        $data['patient_snapshot'] ?? '',
        implode(', ', $data['chronic_conditions'] ?? []),
        implode(', ', $data['red_flags'] ?? []),
        $data['clinical_note'] ?? '',
    ]));
}

// ── Each case: a distinct known allergy must surface somewhere in the briefing ─

it('mentions a documented penicillin allergy in the briefing', function () {
    [$doctor, $appt] = makeUpcomingAppointment(['known_allergies' => 'Penicillin']);
    $text = briefingText(runBriefing($doctor, $appt));
    expect($text)->toContain('penicillin');
});

it('mentions a documented sulfa drug allergy in the briefing', function () {
    [$doctor, $appt] = makeUpcomingAppointment(['known_allergies' => 'Sulfa drugs']);
    $text = briefingText(runBriefing($doctor, $appt));
    expect($text)->toContain('sulfa');
});

it('mentions a documented latex allergy in the briefing', function () {
    [$doctor, $appt] = makeUpcomingAppointment(['known_allergies' => 'Latex']);
    $text = briefingText(runBriefing($doctor, $appt));
    expect($text)->toContain('latex');
});

it('mentions a documented peanut allergy in the briefing', function () {
    [$doctor, $appt] = makeUpcomingAppointment(['known_allergies' => 'Peanuts']);
    $text = briefingText(runBriefing($doctor, $appt));
    expect($text)->toContain('peanut');
});

it('mentions a documented shellfish allergy alongside a chronic condition in the briefing', function () {
    [$doctor, $appt] = makeUpcomingAppointment([
        'known_allergies' => 'Shellfish',
        'chronic_conditions' => 'Hypertension',
    ]);
    $text = briefingText(runBriefing($doctor, $appt));
    expect($text)->toContain('shellfish');
});
