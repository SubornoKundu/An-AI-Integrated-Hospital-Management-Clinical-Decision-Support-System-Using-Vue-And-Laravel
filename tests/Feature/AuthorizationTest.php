<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\StaffAdmin;

it('redirects a guest away from the patient dashboard', function () {
    $this->get('/patient/dashboard')->assertRedirect();
    $this->assertGuest('patient');
});

it('redirects a guest away from the doctor dashboard', function () {
    $this->get('/doctor/dashboard')->assertRedirect();
});

it('redirects a guest away from the admin dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect();
});

it('blocks a logged-in patient from doctor routes', function () {
    $patient = Patient::factory()->create();

    $response = $this->actingAs($patient, 'patient')->get('/doctor/dashboard');

    $response->assertRedirect();
});

it('blocks a logged-in patient from admin routes', function () {
    $patient = Patient::factory()->create();

    $response = $this->actingAs($patient, 'patient')->get('/admin/dashboard');

    $response->assertRedirect();
});

it('blocks a logged-in doctor from admin routes', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->actingAs($doctor, 'doctor')->get('/admin/dashboard');

    $response->assertRedirect();
});

it('blocks a logged-in doctor from patient routes', function () {
    $doctor = Doctor::factory()->create();

    $response = $this->actingAs($doctor, 'doctor')->get('/patient/dashboard');

    $response->assertRedirect();
});

it('blocks an admin session from doctor routes', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_name' => $admin->name]);

    $response = $this->get('/doctor/dashboard');

    $response->assertRedirect();
});

it('allows a logged-in patient to reach their own dashboard', function () {
    $patient = Patient::factory()->create();

    $response = $this->actingAs($patient, 'patient')->get('/patient/dashboard');

    $response->assertOk();
});

it('allows an admin session to reach the admin dashboard', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_name' => $admin->name]);

    $response = $this->get('/admin/dashboard');

    $response->assertOk();
});
