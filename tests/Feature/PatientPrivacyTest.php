<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;

function makeAppointment(Patient $patient, Doctor $doctor, array $overrides = []): Appointment
{
    return Appointment::create(array_merge([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_date' => now()->addDay()->toDateString(),
        'slot_start' => '10:00',
        'slot_end' => '10:15',
        'status' => 'active',
        'consultancy_fee' => $doctor->consultancy_fee,
    ], $overrides));
}

it('does not let a patient cancel another patient\'s appointment', function () {
    $owner = Patient::factory()->create();
    $attacker = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $appointment = makeAppointment($owner, $doctor);

    $this->actingAs($attacker, 'patient')
        ->post("/patient/appointments/{$appointment->id}/cancel")
        ->assertStatus(404);

    expect($appointment->fresh()->status)->toBe('active');
});

it('does not let a patient download another patient\'s invoice', function () {
    $owner = Patient::factory()->create();
    $attacker = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $appointment = makeAppointment($owner, $doctor, ['status' => 'completed']);

    $this->actingAs($attacker, 'patient')
        ->get("/patient/appointments/{$appointment->id}/invoice")
        ->assertStatus(404);
});

it('does not let a patient download another patient\'s prescription', function () {
    $owner = Patient::factory()->create();
    $attacker = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $appointment = makeAppointment($owner, $doctor, ['status' => 'completed']);
    $prescription = Prescription::create([
        'appointment_id' => $appointment->id,
        'patient_id' => $owner->id,
        'doctor_id' => $doctor->id,
        'diagnosis' => 'Test diagnosis',
        'medicines' => 'Test medicine',
    ]);

    $this->actingAs($attacker, 'patient')
        ->get("/patient/prescription/{$prescription->id}/download")
        ->assertStatus(404);
});

it('lets a patient cancel their own appointment', function () {
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $appointment = makeAppointment($patient, $doctor);

    $this->actingAs($patient, 'patient')
        ->post("/patient/appointments/{$appointment->id}/cancel")
        ->assertRedirect();

    expect($appointment->fresh()->status)->toBe('cancelled_by_patient');
});

it('does not let a doctor complete another doctor\'s appointment', function () {
    $owner = Doctor::factory()->create();
    $attacker = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    $appointment = makeAppointment($patient, $owner);

    $this->actingAs($attacker, 'doctor')
        ->post("/doctor/appointments/{$appointment->id}/complete")
        ->assertStatus(404);

    expect($appointment->fresh()->status)->toBe('active');
});

it('does not let a doctor write a prescription against another doctor\'s appointment', function () {
    $owner = Doctor::factory()->create();
    $attacker = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    $appointment = makeAppointment($patient, $owner, ['status' => 'completed']);

    $this->actingAs($attacker, 'doctor')
        ->post("/doctor/prescription/{$appointment->id}", [
            'diagnosis' => 'Attacker-written diagnosis',
            'medicines' => 'Attacker-written medicine',
        ])
        ->assertStatus(404);

    expect(Prescription::where('appointment_id', $appointment->id)->exists())->toBeFalse();
});

it('lets a doctor view their own patient\'s appointment history', function () {
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    makeAppointment($patient, $doctor, ['status' => 'completed']);

    $this->actingAs($doctor, 'doctor')
        ->get('/doctor/appointments')
        ->assertOk();
});
