<?php

use App\Models\BedAdmission;
use App\Models\Doctor;
use App\Models\HospitalBed;
use App\Models\Patient;
use App\Models\StaffAdmin;

function actingAsAdmin(StaffAdmin $admin): void
{
    session(['admin_logged_in' => true, 'admin_id' => $admin->id, 'admin_name' => $admin->name]);
}

it('prevents two patients from being admitted to the same bed', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    $bed = HospitalBed::factory()->create(['status' => 'available']);
    $doctor = Doctor::factory()->create();
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    actingAsAdmin($admin);
    $first = $this->post('/admin/beds/admit', [
        'bed_id' => $bed->id,
        'patient_id' => $patientA->id,
        'doctor_id' => $doctor->id,
        'admission_date' => now()->toDateString(),
    ]);

    $second = $this->post('/admin/beds/admit', [
        'bed_id' => $bed->id,
        'patient_id' => $patientB->id,
        'doctor_id' => $doctor->id,
        'admission_date' => now()->toDateString(),
    ]);

    expect(BedAdmission::where('bed_id', $bed->id)->where('status', 'admitted')->count())->toBe(1);
    $second->assertSessionHas('error');
    expect($bed->fresh()->status)->toBe('occupied');
});

it('allows a new admission after the bed is freed by discharge', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    $bed = HospitalBed::factory()->create(['status' => 'available']);
    $doctor = Doctor::factory()->create();
    $patientA = Patient::factory()->create();
    $patientB = Patient::factory()->create();

    actingAsAdmin($admin);
    $this->post('/admin/beds/admit', [
        'bed_id' => $bed->id,
        'patient_id' => $patientA->id,
        'doctor_id' => $doctor->id,
        'admission_date' => now()->toDateString(),
    ]);
    $admission = BedAdmission::where('bed_id', $bed->id)->first();

    $this->put("/admin/beds/admissions/{$admission->id}/discharge", [
        'discharge_date' => now()->toDateString(),
    ]);

    $rebook = $this->post('/admin/beds/admit', [
        'bed_id' => $bed->id,
        'patient_id' => $patientB->id,
        'doctor_id' => $doctor->id,
        'admission_date' => now()->toDateString(),
    ]);

    expect(BedAdmission::where('bed_id', $bed->id)->where('status', 'admitted')->count())->toBe(1);
    expect(BedAdmission::where('bed_id', $bed->id)->where('patient_id', $patientB->id)->exists())->toBeTrue();
});

it('does not let ward or bed deletion remove a bed with an active admission', function () {
    $admin = StaffAdmin::factory()->create(['role' => 'admin']);
    $bed = HospitalBed::factory()->create(['status' => 'occupied']);

    actingAsAdmin($admin);
    $response = $this->delete("/admin/beds/bed/{$bed->id}");

    expect(HospitalBed::find($bed->id))->not->toBeNull();
});
