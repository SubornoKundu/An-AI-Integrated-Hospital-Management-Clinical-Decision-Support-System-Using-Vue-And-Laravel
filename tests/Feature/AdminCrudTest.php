<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorCategory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\StaffAdmin;

it('lets an admin create a doctor', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    $category = DoctorCategory::factory()->create();
    session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_name' => $admin->name]);

    $response = $this->post('/admin/doctors', [
        'name' => 'Dr. Test Person',
        'email' => 'newdoctor@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'category_id' => $category->id,
        'consultancy_fee' => 900,
        'gender' => 'Male',
    ]);

    $response->assertRedirect();
    expect(Doctor::where('email', 'newdoctor@example.com')->exists())->toBeTrue();
});

it('rejects creating a doctor with a duplicate email', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    $existing = Doctor::factory()->create();
    session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_name' => $admin->name]);

    $response = $this->post('/admin/doctors', [
        'name' => 'Dr. Duplicate',
        'email' => $existing->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'category_id' => DoctorCategory::factory()->create()->id,
        'consultancy_fee' => 500,
        'gender' => 'Male',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Doctor::where('email', $existing->email)->count())->toBe(1);
});

it('soft-deletes a doctor instead of removing the row, preserving medical history', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    $appointment = Appointment::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'appointment_date' => now()->subDays(10)->toDateString(),
        'slot_start' => '10:00',
        'slot_end' => '10:15',
        'status' => 'completed',
        'consultancy_fee' => $doctor->consultancy_fee,
    ]);
    Prescription::create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'diagnosis' => 'Historical diagnosis',
        'medicines' => 'Historical medicine',
    ]);

    session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_name' => $admin->name]);
    $response = $this->delete("/admin/doctors/{$doctor->id}");

    $response->assertRedirect();

    // The row must still exist in the database (soft-deleted), and the
    // patient's historical records must still resolve the doctor's name —
    // this is the actual regression this test guards against.
    $this->assertSoftDeleted('doctors', ['id' => $doctor->id]);
    expect(Appointment::find($appointment->id))->not->toBeNull();
    expect($appointment->fresh()->doctor)->not->toBeNull();
    expect($appointment->fresh()->doctor->id)->toBe($doctor->id);
    expect(Prescription::where('appointment_id', $appointment->id)->first()->doctor)->not->toBeNull();
});

it('no longer lists a soft-deleted doctor for new bookings', function () {
    $doctor = Doctor::factory()->create();
    $doctor->delete();

    expect(Doctor::find($doctor->id))->toBeNull();
    expect(Doctor::withTrashed()->find($doctor->id))->not->toBeNull();
});

it('does not let a soft-deleted doctor log in', function () {
    $doctor = Doctor::factory()->create(['password' => bcrypt('correct-password')]);
    $email = $doctor->email;
    $doctor->delete();

    $response = $this->post('/login/doctor', [
        'email' => $email,
        'password' => 'correct-password',
    ]);

    $this->assertGuest('doctor');
});

it('blocks admin deletion when it would remove the last admin account', function () {
    $onlyAdmin = StaffAdmin::factory()->create(['role' => 'admin']);
    session(['admin_logged_in' => true, 'admin_id' => $onlyAdmin->id, 'admin_name' => $onlyAdmin->name]);

    $response = $this->delete("/admin/admins/{$onlyAdmin->id}");

    expect(StaffAdmin::find($onlyAdmin->id))->not->toBeNull();
});
