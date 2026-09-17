<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BedAdmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'bed_id', 'patient_id', 'doctor_id',
        'admission_date', 'discharge_date',
        'diagnosis', 'notes', 'status', 'total_charge',
    ];

    protected $casts = [
        'admission_date'  => 'date',
        'discharge_date'  => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────
    public function bed()
    {
        return $this->belongsTo(HospitalBed::class, 'bed_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    // ── Computed ─────────────────────────────────────────────────
    public function getDaysAdmittedAttribute(): int
    {
        $end = $this->discharge_date ?? Carbon::today();
        return max(1, $this->admission_date->diffInDays($end));
    }

    public function getCalculatedChargeAttribute(): float
    {
        return $this->daysAdmitted * ($this->bed?->daily_charge ?? 0);
    }
}
