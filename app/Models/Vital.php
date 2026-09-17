<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vital extends Model
{
    protected $fillable = [
        'appointment_id', 'patient_id',
        'bp_systolic', 'bp_diastolic', 'pulse_rate', 'temperature_celsius', 'spo2',
    ];

    protected $casts = [
        'temperature_celsius' => 'decimal:1',
    ];

    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function patient()    { return $this->belongsTo(Patient::class); }
}
