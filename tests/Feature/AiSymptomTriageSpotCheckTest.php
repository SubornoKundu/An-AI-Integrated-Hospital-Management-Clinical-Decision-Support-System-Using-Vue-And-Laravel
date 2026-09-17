<?php

use App\Models\Patient;

/*
|--------------------------------------------------------------------------
| AI Symptom Triage — small-scale spot check
|--------------------------------------------------------------------------
| Same philosophy as AiPrescriptionSafetySpotCheckTest.php: a small,
| hand-picked set of textbook-unambiguous presentations (classic
| emergency red-flags vs. clearly routine complaints), run against the
| real production prompt via the app's own callGroq() wrapper. This is
| NOT a clinical validation study — it checks whether the urgency level
| lands in the right broad band for cases where any triage reference
| (e.g. the Emergency Severity Index) would agree on the answer.
|
| Run with: ./vendor/bin/pest tests/Feature/AiSymptomTriageSpotCheckTest.php
|--------------------------------------------------------------------------
*/

function runSymptomCheck(Patient $patient, string $symptoms): array
{
    $response = test()->actingAs($patient, 'patient')
        ->post('/patient/ai/symptom-checker/analyze', ['symptoms' => $symptoms]);

    $response->assertOk();

    return $response->json();
}

// ── Classic emergency presentations (should return urgency = emergency) ────

it('flags crushing chest pain with sweating and arm pain as emergency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'Crushing chest pain radiating to my left arm, sweating heavily, it started about 20 minutes ago and is not going away.');
    expect($data['urgency'])->toBe('emergency');
});

it('flags sudden one-sided weakness and facial drooping as emergency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'Sudden weakness on the right side of my face and I cannot lift my right arm, started 10 minutes ago, my speech also sounds slurred.');
    expect($data['urgency'])->toBe('emergency');
});

it('flags a sudden worst-ever headache with neck stiffness as emergency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'Sudden onset of the worst headache of my life about an hour ago, along with a stiff neck and sensitivity to light.');
    expect($data['urgency'])->toBe('emergency');
});

it('flags high fever with neck stiffness and confusion as emergency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'High fever of 104 F since this morning, severe neck stiffness, and I am confused and not making sense according to my family.');
    expect($data['urgency'])->toBe('emergency');
});

it('flags severe breathing difficulty with blue lips as emergency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'Severe difficulty breathing that started suddenly, I can barely speak in full sentences, and my lips look bluish.');
    expect($data['urgency'])->toBe('emergency');
});

// ── Clearly routine presentations (should return urgency = low) ────────────

it('marks a one-day mild sore throat with no fever as low urgency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'Mild sore throat for one day, no fever, no difficulty swallowing, otherwise feeling completely fine.');
    expect($data['urgency'])->toBe('low');
});

it('marks a small stopped-bleeding paper cut as low urgency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'I got a small paper cut on my finger an hour ago, it bled a little but has completely stopped now, just a bit sore.');
    expect($data['urgency'])->toBe('low');
});

it('marks a stubbed toe with mild swelling as low urgency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'I stubbed my toe on the stairs yesterday, it is a bit swollen and sore but I can still walk on it fine.');
    expect($data['urgency'])->toBe('low');
});

it('marks mild seasonal allergy symptoms as low urgency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'Runny nose and sneezing for the past few days, seems like my usual seasonal allergies, no fever or other symptoms.');
    expect($data['urgency'])->toBe('low');
});

// ── A genuinely intermediate case (should be medium or high, not the extremes) ─

it('marks a three-week persistent dry cough as medium or high, not low or emergency', function () {
    $patient = Patient::factory()->create();
    $data = runSymptomCheck($patient, 'Persistent dry cough for three weeks now, no fever, no chest pain, no other symptoms, just will not go away.');
    expect($data['urgency'])->toBeIn(['medium', 'high']);
});
