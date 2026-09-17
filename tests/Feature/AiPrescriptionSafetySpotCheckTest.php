<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;

/*
|--------------------------------------------------------------------------
| AI Prescription Safety Check — small-scale spot check
|--------------------------------------------------------------------------
| This is NOT a clinical validation study. It is a small, hand-picked set
| of well-documented, textbook-level drug interactions, allergy conflicts,
| and safe control cases, used to give an honest, reproducible number for
| how the AI Prescription Safety Check behaves — instead of an invented
| accuracy percentage. Every "should flag" case below is a widely
| documented interaction/contraindication (standard pharmacology
| references), not an invented scenario. Every "should NOT flag" case is
| a routine, uncomplicated prescription.
|
| Each test makes a REAL call to the Groq API through the app's own
| callGroq() wrapper — this exercises the actual prompt used in
| production, not a mock. Run with: php artisan test --filter=SpotCheck
| (needs a valid GROQ_API_KEY in .env). Pest's own pass/fail output for
| this file — e.g. "13 passed, 1 failed" — is the number to report in
| Table 5.1 / Section 5.3, and each test name documents exactly what was
| tested, so the output can be screenshotted directly for the report or
| shown live at defense.
|--------------------------------------------------------------------------
*/

function makeDoctorAndPatient(array $patientOverrides = []): array
{
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create($patientOverrides);

    return [$doctor, $patient];
}

function makeCompletedAppointment(Patient $patient, Doctor $doctor): Appointment
{
    return Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_date' => now()->subDay()->toDateString(),
        'slot_start' => '10:00',
        'slot_end' => '10:15',
        'status' => 'completed',
        'consultancy_fee' => $doctor->consultancy_fee,
    ]);
}

/**
 * Runs a safety-check case and returns the decoded AI response.
 */
function runSafetyCheck(Doctor $doctor, Appointment $appointment, string $diagnosis, string $medicines): array
{
    $response = test()->actingAs($doctor, 'doctor')
        ->post("/doctor/prescription/{$appointment->id}/safety-check", [
            'diagnosis' => $diagnosis,
            'medicines' => $medicines,
        ]);

    $response->assertOk();

    return $response->json();
}

function flaggedSomething(array $data): bool
{
    return count($data['critical_issues'] ?? []) > 0 || count($data['warnings'] ?? []) > 0;
}

function flaggedNothing(array $data): bool
{
    return count($data['critical_issues'] ?? []) === 0 && count($data['warnings'] ?? []) === 0;
}

// ── Known drug-drug interactions (should be flagged) ───────────────────────

it('flags warfarin plus new aspirin as a bleeding risk', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['current_medications' => 'Warfarin 5mg once daily']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Mild joint pain', 'Aspirin 300mg as needed');

    expect(flaggedSomething($data))->toBeTrue();
});

it('flags an MAOI plus a new SSRI as a serotonin syndrome risk', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['current_medications' => 'Phenelzine 15mg three times daily (MAOI, for depression)']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Low mood, second opinion', 'Fluoxetine 20mg once daily');

    expect(flaggedSomething($data))->toBeTrue();
});

it('flags a nitrate plus new sildenafil as a hypotension risk', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['current_medications' => 'Isosorbide mononitrate 30mg once daily']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Erectile dysfunction', 'Sildenafil 50mg as needed');

    expect(flaggedSomething($data))->toBeTrue();
});

it('flags simvastatin plus new clarithromycin as a rhabdomyolysis risk', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['current_medications' => 'Simvastatin 40mg once daily']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Bacterial chest infection', 'Clarithromycin 500mg twice daily');

    expect(flaggedSomething($data))->toBeTrue();
});

it('flags an ACE inhibitor plus new spironolactone as a hyperkalaemia risk', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['current_medications' => 'Lisinopril 10mg once daily']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Fluid retention', 'Spironolactone 25mg once daily');

    expect(flaggedSomething($data))->toBeTrue();
});

// ── Allergy conflicts (should be flagged) ───────────────────────────────────

it('flags amoxicillin for a patient with a documented penicillin allergy', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['known_allergies' => 'Penicillin (documented rash reaction)']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Suspected bacterial throat infection', 'Amoxicillin 500mg three times daily');

    expect(flaggedSomething($data))->toBeTrue();
});

it('flags cephalexin for a patient with a documented penicillin allergy (cross-reactivity)', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['known_allergies' => 'Penicillin (documented rash reaction)']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Skin infection', 'Cephalexin 500mg four times daily');

    expect(flaggedSomething($data))->toBeTrue();
});

it('flags co-trimoxazole for a patient with a documented sulfa allergy', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['known_allergies' => 'Sulfa drugs (documented hives)']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Urinary tract infection', 'Co-trimoxazole 960mg twice daily');

    expect(flaggedSomething($data))->toBeTrue();
});

// ── Drug-disease contraindications (should be flagged) ──────────────────────

it('flags ibuprofen for a patient with known peptic ulcer disease', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['chronic_conditions' => 'Peptic ulcer disease']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Lower back pain', 'Ibuprofen 400mg three times daily');

    expect(flaggedSomething($data))->toBeTrue();
});

it('flags propranolol for a patient with known asthma', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['chronic_conditions' => 'Asthma, uses salbutamol inhaler as needed']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Performance anxiety', 'Propranolol 40mg once daily');

    expect(flaggedSomething($data))->toBeTrue();
});

// ── Duplicate therapy (should be flagged) ───────────────────────────────────

it('flags a second statin prescribed on top of an existing statin', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['current_medications' => 'Atorvastatin 20mg once daily']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'High cholesterol, follow-up', 'Simvastatin 20mg once daily');

    expect(flaggedSomething($data))->toBeTrue();
});

// ── Safe control cases (should NOT be flagged) ──────────────────────────────

it('does not flag paracetamol for a patient with no allergies or other medicines', function () {
    [$doctor, $patient] = makeDoctorAndPatient();
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Mild fever', 'Paracetamol 500mg as needed');

    expect(flaggedNothing($data))->toBeTrue();
});

it('does not flag amoxicillin for a patient with no allergies and no interacting medicines', function () {
    [$doctor, $patient] = makeDoctorAndPatient();
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Simple bacterial throat infection', 'Amoxicillin 500mg three times daily');

    expect(flaggedNothing($data))->toBeTrue();
});

it('does not flag metformin for a patient whose only known condition is diabetes', function () {
    [$doctor, $patient] = makeDoctorAndPatient(['chronic_conditions' => 'Type 2 diabetes']);
    $appt = makeCompletedAppointment($patient, $doctor);

    $data = runSafetyCheck($doctor, $appt, 'Type 2 diabetes, routine follow-up', 'Metformin 500mg twice daily');

    expect(flaggedNothing($data))->toBeTrue();
});
