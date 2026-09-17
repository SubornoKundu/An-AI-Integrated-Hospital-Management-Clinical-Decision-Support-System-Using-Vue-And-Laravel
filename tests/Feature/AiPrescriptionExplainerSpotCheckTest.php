<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;

/*
|--------------------------------------------------------------------------
| AI Prescription Explainer — small-scale faithfulness spot check
|--------------------------------------------------------------------------
| This does not test "is the advice good" (that is subjective). It tests
| something narrower and objectively checkable: does the plain-language
| explanation correctly list every medicine that was actually prescribed,
| without inventing one that was not? This is a faithfulness/hallucination
| check on the real production prompt, via the app's own callGroq()
| wrapper — not a mock.
|
| Run with: ./vendor/bin/pest tests/Feature/AiPrescriptionExplainerSpotCheckTest.php
|--------------------------------------------------------------------------
*/

function makePrescribedAppointment(array $rxOverrides = []): array
{
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_date' => now()->subDay()->toDateString(),
        'slot_start' => '10:00',
        'slot_end' => '10:15',
        'status' => 'completed',
        'consultancy_fee' => $doctor->consultancy_fee,
    ]);

    Prescription::create(array_merge([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'instructions' => 'Take as directed. Drink plenty of water.',
        'follow_up_date' => now()->addDays(7)->toDateString(),
    ], $rxOverrides));

    return [$patient, $appointment];
}

function runExplainer(Patient $patient, Appointment $appointment): array
{
    $response = test()->actingAs($patient, 'patient')
        ->post("/patient/ai/prescription/{$appointment->id}/explain");

    $response->assertOk();

    return $response->json();
}

function medicineNamesMentioned(array $data): string
{
    // Flatten every medicine name the AI returned into one lowercase string,
    // so a simple case-insensitive "does it contain X" check can be used per drug.
    return strtolower(collect($data['medicines'] ?? [])->pluck('name')->implode(' | '));
}

// ── Two medicines, both must appear, none invented ──────────────────────────

it('lists both prescribed medicines for a two-drug prescription', function () {
    [$patient, $appt] = makePrescribedAppointment([
        'diagnosis' => 'Bacterial throat infection',
        'medicines' => 'Amoxicillin 500mg three times daily for 7 days; Paracetamol 500mg as needed for fever',
    ]);

    $data = runExplainer($patient, $appt);
    $names = medicineNamesMentioned($data);

    expect($data['medicines'])->toHaveCount(2)
        ->and($names)->toContain('amoxicillin')
        ->and($names)->toContain('paracetamol');
});

it('lists all three prescribed medicines for a three-drug prescription', function () {
    [$patient, $appt] = makePrescribedAppointment([
        'diagnosis' => 'Type 2 diabetes and hypertension, routine follow-up',
        'medicines' => 'Metformin 500mg twice daily; Amlodipine 5mg once daily; Atorvastatin 10mg once daily at night',
    ]);

    $data = runExplainer($patient, $appt);
    $names = medicineNamesMentioned($data);

    expect($data['medicines'])->toHaveCount(3)
        ->and($names)->toContain('metformin')
        ->and($names)->toContain('amlodipine')
        ->and($names)->toContain('atorvastatin');
});

it('lists the single prescribed medicine for a one-drug prescription without adding extras', function () {
    [$patient, $appt] = makePrescribedAppointment([
        'diagnosis' => 'Mild seasonal allergy',
        'medicines' => 'Cetirizine 10mg once daily at night',
    ]);

    $data = runExplainer($patient, $appt);
    $names = medicineNamesMentioned($data);

    expect($data['medicines'])->toHaveCount(1)
        ->and($names)->toContain('cetirizine');
});

it('lists all four prescribed medicines for a more complex prescription', function () {
    [$patient, $appt] = makePrescribedAppointment([
        'diagnosis' => 'Acute gastritis with reflux',
        'medicines' => 'Omeprazole 20mg once daily before breakfast; Domperidone 10mg three times daily before meals; Sucralfate 1g four times daily; Paracetamol 500mg as needed for pain',
    ]);

    $data = runExplainer($patient, $appt);
    $names = medicineNamesMentioned($data);

    expect($data['medicines'])->toHaveCount(4)
        ->and($names)->toContain('omeprazole')
        ->and($names)->toContain('domperidone')
        ->and($names)->toContain('sucralfate')
        ->and($names)->toContain('paracetamol');
});

it('carries forward a doctor override note into a non-alarming safety_note instead of dropping it', function () {
    [$patient, $appt] = makePrescribedAppointment([
        'diagnosis' => 'Bacterial sinus infection',
        'medicines' => 'Amoxicillin 500mg three times daily for 7 days',
        'safety_check_override' => 'Patient has a mild penicillin-family allergy note on file; doctor confirmed with patient this specific drug has been tolerated before and proceeded.',
    ]);

    $data = runExplainer($patient, $appt);

    expect($data['safety_note'])->not->toBeNull();
});
