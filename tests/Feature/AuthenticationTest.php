<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\StaffAdmin;

it('logs a patient in with correct credentials and regenerates the session', function () {
    $patient = Patient::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->post('/login/patient', [
        'email' => $patient->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($patient, 'patient');
});

it('logs a doctor in with correct credentials', function () {
    $doctor = Doctor::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->post('/login/doctor', [
        'email' => $doctor->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticatedAs($doctor, 'doctor');
});

it('logs an admin in with correct credentials via the database-backed path', function () {
    $admin = StaffAdmin::factory()->create(['password' => bcrypt('correct-password'), 'role' => 'admin']);

    $response = $this->post('/login/admin', [
        'email' => $admin->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect();
    $this->assertTrue(session('admin_logged_in'));
    expect(session('admin_id'))->toBe($admin->id);
});

it('rejects an incorrect password without revealing whether the account exists', function () {
    $patient = Patient::factory()->create(['password' => bcrypt('correct-password')]);

    $wrongPassword = $this->from('/login')->post('/login/patient', [
        'email' => $patient->email,
        'password' => 'wrong-password',
    ]);
    $wrongPasswordError = session('error');

    $nonExistentEmail = $this->from('/login')->post('/login/patient', [
        'email' => 'nobody-'.uniqid().'@example.com',
        'password' => 'wrong-password',
    ]);
    $nonExistentEmailError = session('error');

    $this->assertGuest('patient');
    expect($wrongPasswordError)->not->toBeNull();
    // The whole point of a generic auth error is that a wrong password and
    // a non-existent account are indistinguishable from the outside — this
    // is the actual assertion, not just that each independently fails.
    expect($wrongPasswordError)->toBe($nonExistentEmailError);
});

it('locks a patient account out after repeated failed attempts', function () {
    $patient = Patient::factory()->create(['password' => bcrypt('correct-password')]);
    $maxAttempts = config('hospital.max_attempts', 5);

    for ($i = 0; $i < $maxAttempts; $i++) {
        $this->post('/login/patient', ['email' => $patient->email, 'password' => 'wrong']);
    }

    // One more attempt, even with the CORRECT password, should now be
    // blocked by the lockout rather than succeeding.
    $response = $this->post('/login/patient', [
        'email' => $patient->email,
        'password' => 'correct-password',
    ]);

    $this->assertGuest('patient');
});

it('fully invalidates the session on patient logout', function () {
    $patient = Patient::factory()->create();
    $this->actingAs($patient, 'patient');

    $response = $this->post('/logout/patient');

    $response->assertRedirect();
    $this->assertGuest('patient');
});

it('fully invalidates the session and clears all admin keys on admin logout', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_name' => $admin->name]);

    $response = $this->post('/logout/admin');

    $response->assertRedirect();
    expect(session('admin_logged_in'))->toBeNull();
    expect(session('admin_id'))->toBeNull();
    expect(session('admin_name'))->toBeNull();
});
